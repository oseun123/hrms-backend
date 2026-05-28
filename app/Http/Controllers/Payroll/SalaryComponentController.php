<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Payroll\SalaryComponent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SalaryComponentController extends Controller
{
    /**
     * Display a listing of components.
     */
    public function index()
    {
        $components = SalaryComponent::all();

        return response()->json([
            'success' => true,
            'data' => $components
        ]);
    }

    /**
     * Store a newly created component.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:payroll_salary_components,code',
            'type' => 'required|in:earning,deduction',
            'category' => 'required|in:fixed,variable,statutory,voluntary',
            'is_taxable' => 'boolean',
            'is_tax_deductible' => 'boolean',
            'calculation_type' => 'required|in:fixed,percentage,formula',
            'amount_value' => 'required|numeric',
            'formula' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $component = SalaryComponent::create(array_merge($request->all(), [
            'tenant_id' => auth('sanctum')->user()->tenant_id,
            'created_by' => auth('sanctum')->id(),
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Salary component created successfully',
            'data' => $component
        ], 201);
    }

    /**
     * Display the specified component.
     */
    public function show($id)
    {
        $component = SalaryComponent::find($id);

        if (!$component) {
            return response()->json(['success' => false, 'message' => 'Component not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $component]);
    }

    /**
     * Update the specified component.
     */
    public function update(Request $request, $id)
    {
        $component = SalaryComponent::find($id);

        if (!$component) {
            return response()->json(['success' => false, 'message' => 'Component not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:payroll_salary_components,code,' . $id,
            'type' => 'required|in:earning,deduction',
            'category' => 'required|in:fixed,variable,statutory,voluntary',
            'is_taxable' => 'boolean',
            'is_tax_deductible' => 'boolean',
            'calculation_type' => 'required|in:fixed,percentage,formula',
            'amount_value' => 'required|numeric',
            'formula' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $component->update(array_merge($request->all(), [
            'updated_by' => auth('sanctum')->id(),
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Salary component updated successfully',
            'data' => $component
        ]);
    }

    /**
     * Remove the specified component.
     */
    public function destroy($id)
    {
        $component = SalaryComponent::find($id);

        if (!$component) {
            return response()->json(['success' => false, 'message' => 'Component not found'], 404);
        }

        // Check for usage
        if ($component->payGroups()->exists()) {
            return response()->json(['success' => false, 'message' => 'Cannot delete component in use by pay groups'], 400);
        }

        $component->delete();

        return response()->json(['success' => true, 'message' => 'Salary component deleted successfully']);
    }
}
