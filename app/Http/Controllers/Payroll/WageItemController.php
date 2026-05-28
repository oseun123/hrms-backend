<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Payroll\WageItem;
use App\Models\Payroll\WageItemComponent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WageItemController extends Controller
{
    /**
     * Display a listing of wage items.
     */
    public function index()
    {
        $items = WageItem::with('components.component')->get();

        return response()->json([
            'success' => true,
            'data' => $items
        ]);
    }

    /**
     * Store a newly created wage item.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'has_leave_allowance' => 'nullable|boolean',
            'components' => 'required|array',
            'components.*.name' => 'required|string|max:255',
            'components.*.code' => 'nullable|string|max:50',
            'components.*.amount_value' => 'nullable|numeric|min:0',
            'components.*.type' => 'nullable|string|in:earning,deduction',
            'components.*.category' => 'nullable|string|in:fixed,variable,statutory,voluntary',
            'components.*.is_taxable' => 'nullable|boolean',
            'components.*.is_tax_deductible' => 'nullable|boolean',
            'components.*.show_on_payslip' => 'nullable|boolean',
            'components.*.is_pensionable' => 'nullable|boolean',
            'components.*.frequency' => 'nullable|string|in:monthly,annual',
            'components.*.payment_month' => 'nullable|string',
            'components.*.calculation_type' => 'nullable|string|in:fixed,percent_of_gross,percent_of_basic',
            'components.*.percent_value' => 'nullable|numeric|min:0',

        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $wageItem = WageItem::create([
                'tenant_id' => auth()->user()->tenant_id,
                'name' => $request->name,
                'description' => $request->description,
                'has_leave_allowance' => $request->boolean('has_leave_allowance'),
                'created_by' => auth()->user()->id,
            ]);

            foreach ($request->components as $compData) {
                // Determine Code: Use name if not system component
                $name = $compData['name'];
                $code = $compData['code'] ?? (strtoupper(substr($name, 0, 3)) . '_' . time());

                // Special mapping for system defined
                if (stripos($name, 'basic') !== false || ($compData['code'] ?? '') === 'BASIC') {
                    $code = 'BASIC';
                    $name = 'Basic Salary';
                }
                if (stripos($name, 'housing') !== false || ($compData['code'] ?? '') === 'HOU') {
                    $code = 'HOU';
                    $name = 'Housing Allowance';
                }
                if (stripos($name, 'transport') !== false || ($compData['code'] ?? '') === 'TRA') {
                    $code = 'TRA';
                    $name = 'Transport Allowance';
                }
                if (stripos($name, 'leave allowance') !== false || ($compData['code'] ?? '') === 'LAA') {
                    $code = 'LAA';
                    $name = 'Leave Allowance';
                }

                $component = \App\Models\Payroll\SalaryComponent::updateOrCreate(
                    ['tenant_id' => auth()->user()->tenant_id, 'code' => $code],
                    [
                        'name' => $name,
                        'type' => $compData['type'] ?? 'earning',
                        'category' => $compData['category'] ?? ((in_array($code, ['BASIC', 'HOU', 'TRA'])) ? 'fixed' : 'variable'),
                        'is_taxable' => $compData['is_taxable'] ?? true,
                        'is_tax_deductible' => $compData['is_tax_deductible'] ?? false,
                        'show_on_payslip' => $compData['show_on_payslip'] ?? true,
                        'is_pensionable' => $compData['is_pensionable'] ?? (in_array($code, ['BASIC', 'HOU', 'TRA'])),
                        'calculation_type' => 'fixed',
                        'is_active' => true,
                        'is_system_defined' => in_array($code, ['BASIC', 'HOU', 'TRA', 'LAA']),
                    ]
                );

                $wageItem->components()->create([
                    'component_id' => $component->id,
                    'amount_value' => $compData['amount_value'] ?? 0,
                    'calculation_type' => $compData['calculation_type'] ?? 'fixed',
                    'percent_value' => $compData['percent_value'] ?? null,

                    'frequency' => $compData['frequency'] ?? 'monthly',
                    'payment_month' => $compData['payment_month'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Wage Item created successfully',
                'data' => $wageItem->load('components.component')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified wage item.
     */
    public function update(Request $request, $id)
    {
        $wageItem = WageItem::find($id);

        if (!$wageItem) {
            return response()->json(['success' => false, 'message' => 'Wage Item not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'has_leave_allowance' => 'nullable|boolean',
            'components' => 'required|array',
            'components.*.name' => 'required|string|max:255',
            'components.*.code' => 'nullable|string|max:50',
            'components.*.amount_value' => 'nullable|numeric|min:0',
            'components.*.type' => 'nullable|string|in:earning,deduction',
            'components.*.category' => 'nullable|string|in:fixed,variable,statutory,voluntary',
            'components.*.is_taxable' => 'nullable|boolean',
            'components.*.is_tax_deductible' => 'nullable|boolean',
            'components.*.show_on_payslip' => 'nullable|boolean',
            'components.*.is_pensionable' => 'nullable|boolean',
            'components.*.frequency' => 'nullable|string|in:monthly,annual',
            'components.*.payment_month' => 'nullable|string',
            'components.*.calculation_type' => 'nullable|string|in:fixed,percent_of_gross,percent_of_basic',
            'components.*.percent_value' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $wageItem->update([
                'name' => $request->name,
                'description' => $request->description,
                'has_leave_allowance' => $request->boolean('has_leave_allowance'),
                'updated_by' => auth()->user()->id,
            ]);

            // Simple sync: delete existing overrides and recreate
            $wageItem->components()->delete();

            foreach ($request->components as $compData) {
                $name = $compData['name'];
                $code = $compData['code'] ?? (strtoupper(substr($name, 0, 3)) . '_' . time());

                if (stripos($name, 'basic') !== false || ($compData['code'] ?? '') === 'BASIC') {
                    $code = 'BASIC';
                    $name = 'Basic Salary';
                }
                if (stripos($name, 'housing') !== false || ($compData['code'] ?? '') === 'HOU') {
                    $code = 'HOU';
                    $name = 'Housing Allowance';
                }
                if (stripos($name, 'transport') !== false || ($compData['code'] ?? '') === 'TRA') {
                    $code = 'TRA';
                    $name = 'Transport Allowance';
                }
                if (stripos($name, 'leave allowance') !== false || ($compData['code'] ?? '') === 'LAA') {
                    $code = 'LAA';
                    $name = 'Leave Allowance';
                }

                $component = \App\Models\Payroll\SalaryComponent::updateOrCreate(
                    ['tenant_id' => auth()->user()->tenant_id, 'code' => $code],
                    [
                        'name' => $name,
                        'type' => $compData['type'] ?? 'earning',
                        'category' => $compData['category'] ?? ((in_array($code, ['BASIC', 'HOU', 'TRA'])) ? 'fixed' : 'variable'),
                        'is_taxable' => $compData['is_taxable'] ?? true,
                        'is_tax_deductible' => $compData['is_tax_deductible'] ?? false,
                        'show_on_payslip' => $compData['show_on_payslip'] ?? true,
                        'is_pensionable' => $compData['is_pensionable'] ?? (in_array($code, ['BASIC', 'HOU', 'TRA'])),
                        'calculation_type' => 'fixed',
                        'is_active' => true,
                        'is_system_defined' => in_array($code, ['BASIC', 'HOU', 'TRA', 'LAA']),
                    ]
                );

                $wageItem->components()->create([
                    'component_id' => $component->id,
                    'amount_value' => $compData['amount_value'] ?? 0,
                    'calculation_type' => $compData['calculation_type'] ?? 'fixed',
                    'percent_value' => $compData['percent_value'] ?? null,

                    'frequency' => $compData['frequency'] ?? 'monthly',
                    'payment_month' => $compData['payment_month'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Wage Item updated successfully',
                'data' => $wageItem->load('components.component')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified wage item.
     */
    public function destroy($id)
    {
        $wageItem = WageItem::find($id);

        if (!$wageItem) {
            return response()->json(['success' => false, 'message' => 'Wage Item not found'], 404);
        }

        $wageItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Wage Item deleted successfully'
        ]);
    }
}
