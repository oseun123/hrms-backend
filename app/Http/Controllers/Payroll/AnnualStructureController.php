<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Payroll\AnnualSalaryStructure;
use App\Models\Payroll\PayGroup;
use App\Models\Payroll\SalaryComponent;
use App\Services\Payroll\PayrollEngineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AnnualStructureController extends Controller
{
    protected $payrollEngine;

    public function __construct(PayrollEngineService $payrollEngine)
    {
        $this->payrollEngine = $payrollEngine;
    }

    /**
     * List all annual salary structures.
     */
    public function index(Request $request)
    {
        $query = AnnualSalaryStructure::with(['employee', 'payGroup.taxScheme', 'items.component']);

        if ($request->has('pay_group_id')) {
            $query->where('pay_group_id', $request->pay_group_id);
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('employee_number', 'like', "%{$search}%");
            });
        }

        $structures = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $structures
        ]);
    }

    /**
     * Generate annual salary structures for all employees in a pay group.
     */
    public function generate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pay_group_id' => 'required',
            'ignore_existing' => 'boolean',
            'auto_activate' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $results = [
            'generated' => 0,
            'skipped' => 0,
            'errors' => []
        ];

        if ($request->pay_group_id === 'all') {
            $payGroups = PayGroup::active()->with(['taxScheme', 'employees'])->get();
            foreach ($payGroups as $payGroup) {
                $groupResults = $this->generateForPayGroup($payGroup, $request->ignore_existing ?? false, $request->auto_activate ?? true);
                $results['generated'] += $groupResults['generated'];
                $results['skipped'] += $groupResults['skipped'];
                $results['errors'] = array_merge($results['errors'], $groupResults['errors']);
            }
        } else {
            $payGroup = PayGroup::with(['taxScheme', 'employees'])->find($request->pay_group_id);
            if (!$payGroup) {
                return response()->json(['success' => false, 'message' => 'Pay Group not found'], 404);
            }
            $results = $this->generateForPayGroup($payGroup, $request->ignore_existing ?? false, $request->auto_activate ?? true);
        }

        return response()->json([
            'success' => true,
            'message' => "Process completed: {$results['generated']} generated, {$results['skipped']} skipped.",
            'data' => $results
        ]);
    }

    protected function generateForPayGroup(PayGroup $payGroup, bool $ignoreExisting, bool $autoActivate = true)
    {
        $employees = $payGroup->employees;
        $results = [
            'generated' => 0,
            'skipped' => 0,
            'errors' => []
        ];

        if ($employees->isEmpty()) {
            return $results;
        }

        foreach ($employees as $employee) {
            // Check if structure already exists
            $existing = AnnualSalaryStructure::where('employee_id', $employee->id)->exists();
            if ($existing && $ignoreExisting) {
                $results['skipped']++;
                continue;
            }

            // Get assigned Pay Group and Wage Item
            $assignment = $employee->payGroups()->where('payroll_pay_groups.id', $payGroup->id)->first();
            $wageItemId = $assignment?->pivot?->wage_item_id;

            if (!$wageItemId) {
                $results['errors'][] = "Group {$payGroup->name} | Employee {$employee->employee_number}: No Wage Item assigned.";
                continue;
            }

            $wageItem = \App\Models\Payroll\WageItem::with('components.component')->find($wageItemId);

            try {
                DB::beginTransaction();

                // 1. Setup Gross and Base Calculations
                $totalGross = $payGroup->annual_gross;

                // Pre-calculate amounts based on calculation_type
                $calculatedAmounts = [];
                $basicAmount = 0;

                // Pass 1: Handle Fixed and Percent of Gross
                foreach ($wageItem->components as $wic) {
                    $amount = 0;
                    if ($wic->calculation_type === 'percent_of_gross') {
                        $amount = ($wic->percent_value / 100) * $totalGross;
                    } elseif ($wic->calculation_type === 'fixed') {
                        $amount = $wic->amount_value;
                    }

                    if ($wic->component->code === 'BASIC') {
                        $basicAmount = $amount;
                    }
                    $calculatedAmounts[$wic->id] = $amount;
                }

                // Pass 2: Handle Percent of Basic
                foreach ($wageItem->components as $wic) {
                    if ($wic->calculation_type === 'percent_of_basic') {
                        $calculatedAmounts[$wic->id] = ($wic->percent_value / 100) * $basicAmount;
                    }
                }

                $pensionBase = 0;
                $taxDeductibleItems = [];
                $totalOtherDeductions = 0;
                $items = [];

                foreach ($wageItem->components as $wic) {
                    $component = $wic->component;
                    $amount = $calculatedAmounts[$wic->id] ?? 0;

                    if ($component->type === 'earning') {
                        if ($component->is_pensionable) {
                            $pensionBase += $amount;
                        }
                    }

                    if ($component->type === 'deduction') {
                        $totalOtherDeductions += $amount;
                    }

                    if ($component->is_tax_deductible) {
                        $taxDeductibleItems[] = $amount;
                    }

                    $items[] = [
                        'component_id' => $component->id,
                        'annual_amount' => $amount,
                        'frequency' => $wic->frequency,
                        'payment_month' => $wic->payment_month,
                    ];
                }

                // 2. Run Engine Calculation
                $payInfo = $this->payrollEngine->calculateAnnualStructure(
                    $totalGross,
                    $payGroup->taxScheme,
                    $taxDeductibleItems,
                    $pensionBase,
                    $payGroup->annual_rent,
                    $totalOtherDeductions
                );

                // 3. Create Structure
                // If exists and we are here, it means we should probably delete/overwrite or we just skip (already handled skipped above)
                // But for safety, if we were told NOT to ignore existing, we should probably delete existing first
                if ($existing) {
                    AnnualSalaryStructure::where('employee_id', $employee->id)->delete();
                }

                $structure = AnnualSalaryStructure::create(array_merge($payInfo, [
                    'tenant_id' => $payGroup->tenant_id ?? auth()->user()->tenant_id,
                    'employee_id' => $employee->id,
                    'pay_group_id' => $payGroup->id,
                    'wage_item_id' => $wageItem->id,
                    'status' => $autoActivate ? 'active' : 'inactive'
                ]));

                // 4. Save Items
                foreach ($items as $item) {
                    $structure->items()->create($item);
                }

                DB::commit();
                $results['generated']++;
            } catch (\Exception $e) {
                DB::rollBack();
                $results['errors'][] = "Group {$payGroup->name} | Employee {$employee->employee_number}: " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Display the authenticated employee's active structure.
     */
    public function myActive(Request $request)
    {
        $user = $request->user();
        if (!$user->employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee profile not found'
            ], 404);
        }

        return $this->show($user->employee->id);
    }

    /**
     * Display the specified employee's structure.
     */
    public function show($employee_id)
    {
        $structure = AnnualSalaryStructure::with(['items.component', 'payGroup.taxScheme'])
            ->where('employee_id', $employee_id)
            ->where('status', 'active')
            ->first();

        if (!$structure) {
            return response()->json([
                'success' => false,
                'message' => 'Active salary structure not found for this employee'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $structure
        ]);
    }

    /**
     * Update an individual structure.
     * This allows editing specific amounts for an employee's template.
     */
    public function update(Request $request, $id)
    {
        $structure = AnnualSalaryStructure::find($id);

        if (!$structure) {
            return response()->json([
                'success' => false,
                'message' => 'Structure not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'items' => 'required|array',
            'items.*.component_id' => 'required|exists:payroll_salary_components,id',
            'items.*.annual_amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // 1. Update items and recalculate gross
            $totalGross = 0;
            $pensionBase = 0;
            $taxDeductibleItems = [];
            $totalOtherDeductions = 0;

            // Clear existing items and re-add
            $structure->items()->delete();

            foreach ($request->items as $itemData) {
                $component = SalaryComponent::find($itemData['component_id']);
                if ($component->type === 'earning') {
                    $totalGross += $itemData['annual_amount'];
                    if ($component->is_pensionable) {
                        $pensionBase += $itemData['annual_amount'];
                    }
                }

                if ($component->type === 'deduction') {
                    $totalOtherDeductions += $itemData['annual_amount'];
                }

                if ($component->is_tax_deductible) {
                    $taxDeductibleItems[] = $itemData['annual_amount'];
                }

                $structure->items()->create([
                    'component_id' => $itemData['component_id'],
                    'annual_amount' => $itemData['annual_amount'],
                    'frequency' => $itemData['frequency'] ?? 'monthly',
                    'payment_month' => $itemData['payment_month'] ?? null,
                ]);
            }

            // Use manual gross from request if provided for statutory calculations, otherwise fallback to sum of components
            $grossToUse = $request->total_annual_gross ?? $totalGross;

            // 2. Recalculate with engine
            $payGroup = PayGroup::with('taxScheme')->find($structure->pay_group_id);
            $payInfo = $this->payrollEngine->calculateAnnualStructure(
                $grossToUse,
                $payGroup->taxScheme,
                $taxDeductibleItems,
                $pensionBase,
                $payGroup->annual_rent,
                $totalOtherDeductions
            );

            // 3. APPLY MANUAL OVERRIDES from request if present and detect alterations
            $overrides = $request->only([
                'total_annual_gross',
                'total_annual_taxable',
                'total_annual_tax',
                'total_annual_pension_ee',
                'total_annual_relief',
                'total_annual_net'
            ]);

            // Convert any string values to float/numeric
            $overrides = array_map(function ($val) {
                return !is_null($val) ? round((float)$val, 2) : $val;
            }, array_filter($overrides, fn($v) => !is_null($v)));

            $isAltered = false;
            foreach ($overrides as $key => $val) {
                if (isset($payInfo[$key]) && round((float)$payInfo[$key], 2) !== $val) {
                    $isAltered = true;
                    break;
                }
            }

            $payInfo = array_merge($payInfo, $overrides);
            $payInfo['is_altered'] = $isAltered;

            // 4. Update structure header
            $structure->update($payInfo);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Salary structure updated' . ($isAltered ? ' (manually altered)' : '') . ' and recalculated successfully',
                'data' => $structure->load('items.component')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error updating structure: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview statutory calculations without saving.
     */
    public function preview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pay_group_id' => 'required|exists:payroll_pay_groups,id',
            'total_annual_gross' => 'nullable|numeric|min:0',
            'items' => 'required|array',
            'items.*.component_id' => 'required|exists:payroll_salary_components,id',
            'items.*.annual_amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $payGroup = PayGroup::with('taxScheme')->find($request->pay_group_id);

            $calculatedGross = 0;
            $pensionBase = 0;
            $taxDeductibleItems = [];
            $totalOtherDeductions = 0;

            foreach ($request->items as $itemData) {
                $component = SalaryComponent::find($itemData['component_id']);
                if ($component->type === 'earning') {
                    $calculatedGross += $itemData['annual_amount'];
                    if ($component->is_pensionable) {
                        $pensionBase += $itemData['annual_amount'];
                    }
                }

                if ($component->type === 'deduction') {
                    $totalOtherDeductions += $itemData['annual_amount'];
                }

                if ($component->is_tax_deductible) {
                    $taxDeductibleItems[] = $itemData['annual_amount'];
                }
            }

            // Use manual gross from request if provided, otherwise fallback to calculated sum
            $grossToUse = $request->total_annual_gross ?? $calculatedGross;

            $payInfo = $this->payrollEngine->calculateAnnualStructure(
                $grossToUse,
                $payGroup->taxScheme,
                $taxDeductibleItems,
                $pensionBase,
                $payGroup->annual_rent,
                $totalOtherDeductions
            );

            // Ensure we return the gross that was actually used
            $payInfo['total_annual_gross'] = $grossToUse;

            return response()->json([
                'success' => true,
                'data' => $payInfo
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error calculating preview: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a structure to allow regeneration.
     */
    /**
     * Delete a structure to allow regeneration.
     */
    public function destroy($id)
    {
        $structure = AnnualSalaryStructure::find($id);

        if (!$structure) {
            return response()->json([
                'success' => false,
                'message' => 'Structure not found'
            ], 404);
        }

        $structure->delete();

        return response()->json([
            'success' => true,
            'message' => 'Salary structure deleted successfully'
        ]);
    }

    /**
     * Bulk delete structures.
     */
    public function bulkDestroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:payroll_annual_salary_structures,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            AnnualSalaryStructure::whereIn('id', $request->ids)->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Selected salary structures deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error deleting structures: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle the status of a structure.
     */
    public function toggleStatus($id)
    {
        $structure = AnnualSalaryStructure::find($id);

        if (!$structure) {
            return response()->json([
                'success' => false,
                'message' => 'Structure not found'
            ], 404);
        }

        $structure->update([
            'status' => $structure->status === 'active' ? 'inactive' : 'active'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully',
            'data' => $structure
        ]);
    }

    /**
     * Bulk activate structures.
     */
    public function bulkActivate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:payroll_annual_salary_structures,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        AnnualSalaryStructure::whereIn('id', $request->ids)->update(['status' => 'active']);

        return response()->json([
            'success' => true,
            'message' => 'Selected structures activated successfully'
        ]);
    }
}
