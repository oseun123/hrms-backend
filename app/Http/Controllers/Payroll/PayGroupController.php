<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Payroll\PayGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PayGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $groups = PayGroup::with(['taxScheme', 'wageItems.components.component'])->withCount('employees')->get();

        return response()->json([
            'success' => true,
            'data' => $groups
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'annual_gross' => 'required|numeric|min:0',
            'annual_rent' => 'nullable|numeric|min:0',
            'tax_scheme_id' => 'required|exists:payroll_tax_schemes,id',
            'is_active' => 'boolean',
            'wage_item_ids' => 'nullable|array',
            'wage_item_ids.*' => 'exists:payroll_wage_items,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $payGroup = PayGroup::create([
                'tenant_id' => auth()->user()->tenant_id,
                'name' => $request->name,
                'annual_gross' => $request->annual_gross,
                'annual_rent' => $request->annual_rent ?? 0,
                'tax_scheme_id' => $request->tax_scheme_id,
                'is_active' => $request->is_active ?? true,
                'created_by' => auth()->user()->id,
            ]);

            if ($request->has('wage_item_ids')) {
                $payGroup->wageItems()->sync($request->wage_item_ids);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pay group created successfully',
                'data' => $payGroup->load('wageItems')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error creating pay group: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $payGroup = PayGroup::with(['taxScheme', 'wageItems.components.component', 'employees.user'])->find($id);

        if (!$payGroup) {
            return response()->json([
                'success' => false,
                'message' => 'Pay group not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $payGroup
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $payGroup = PayGroup::find($id);

        if (!$payGroup) {
            return response()->json(['success' => false, 'message' => 'Pay group not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'annual_gross' => 'required|numeric|min:0',
            'annual_rent' => 'nullable|numeric|min:0',
            'tax_scheme_id' => 'required|exists:payroll_tax_schemes,id',
            'is_active' => 'boolean',
            'wage_item_ids' => 'nullable|array',
            'wage_item_ids.*' => 'exists:payroll_wage_items,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $payGroup->update(array_merge($request->all(), [
                'updated_by' => auth()->user()->id
            ]));

            if ($request->has('wage_item_ids')) {
                $payGroup->wageItems()->sync($request->wage_item_ids);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pay group updated successfully',
                'data' => $payGroup->load('wageItems')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $payGroup = PayGroup::find($id);

        if (!$payGroup) {
            return response()->json([
                'success' => false,
                'message' => 'Pay group not found'
            ], 404);
        }

        if ($payGroup->employees()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete pay group with assigned employees'
            ], 400);
        }

        $payGroup->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pay group deleted successfully'
        ]);
    }

    /**
     * Assign employees to pay group and specific wage item.
     */
    public function assignEmployees(Request $request, $id)
    {
        $payGroup = PayGroup::find($id);

        if (!$payGroup) {
            return response()->json([
                'success' => false,
                'message' => 'Pay group not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id',
            'wage_item_id' => 'required|exists:payroll_wage_items,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Validate that this wage item belongs to this pay group
        if (!$payGroup->wageItems()->where('wage_item_id', $request->wage_item_id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'The selected wage item is not allowed for this pay group'
            ], 400);
        }

        // CHECK IF EMPLOYEES ARE ALREADY IN ANOTHER GROUP
        $alreadyAssigned = DB::table('payroll_employee_pay_groups')
            ->whereIn('employee_id', $request->employee_ids)
            ->where('pay_group_id', '!=', $id)
            ->join('employees', 'employees.id', '=', 'payroll_employee_pay_groups.employee_id')
            ->join('payroll_pay_groups', 'payroll_pay_groups.id', '=', 'payroll_employee_pay_groups.pay_group_id')
            ->select('employees.first_name', 'employees.last_name', 'payroll_pay_groups.name as group_name')
            ->get();

        if ($alreadyAssigned->count() > 0) {
            $names = $alreadyAssigned->map(fn($e) => "{$e->first_name} ({$e->group_name})")->implode(', ');
            return response()->json([
                'success' => false,
                'message' => "The following employees are already assigned to other groups: {$names}. Please remove them from their current group before reassignment."
            ], 400);
        }

        foreach ($request->employee_ids as $empId) {
            $payGroup->employees()->syncWithoutDetaching([
                $empId => ['wage_item_id' => $request->wage_item_id]
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Employees assigned to pay group and wage item successfully'
        ]);
    }

    /**
     * Remove employee from pay group.
     */
    public function unassignEmployee(Request $request, $id, $employeeId)
    {
        $payGroup = PayGroup::find($id);

        if (!$payGroup) {
            return response()->json(['success' => false, 'message' => 'Pay group not found'], 404);
        }

        try {
            DB::beginTransaction();

            // Unassign
            $payGroup->employees()->detach($employeeId);

            // DELETE Annual Salary Structure for this employee
            DB::table('payroll_annual_salary_structures')
                ->where('employee_id', $employeeId)
                ->where('pay_group_id', $id)
                ->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Employee removed from pay group successfully. Their annual salary structure has also been cleared.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
