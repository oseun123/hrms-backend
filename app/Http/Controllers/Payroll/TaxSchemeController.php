<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Payroll\TaxScheme;
use App\Models\Payroll\TaxBand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TaxSchemeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $taxSchemes = TaxScheme::with('bands')->get();

        return response()->json([
            'success' => true,
            'data' => $taxSchemes
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:payroll_tax_schemes,name,NULL,id,tenant_id,' . auth()->user()->tenant_id,
            'description' => 'nullable|string',
            'employee_pension_percentage' => 'required|numeric|min:0|max:100',
            'employer_pension_percentage' => 'required|numeric|min:0|max:100',
            'apply_cra' => 'boolean',
            'apply_rent_relief' => 'boolean',
            'rent_relief_max_amount' => 'nullable|numeric|min:0',
            'rent_relief_percentage' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
            'bands' => 'nullable|array',
            'bands.*.lower_limit' => 'required|numeric|min:0',
            'bands.*.upper_limit' => 'nullable|numeric|min:0',
            'bands.*.rate_percentage' => 'required|numeric|min:0|max:100',
            'bands.*.flat_amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $taxScheme = TaxScheme::create([
                'name' => $request->name,
                'description' => $request->description,
                'employee_pension_percentage' => $request->employee_pension_percentage,
                'employer_pension_percentage' => $request->employer_pension_percentage,
                'apply_cra' => $request->apply_cra ?? false,
                'apply_rent_relief' => $request->apply_rent_relief ?? false,
                'rent_relief_max_amount' => $request->rent_relief_max_amount ?? 500000,
                'rent_relief_percentage' => $request->rent_relief_percentage ?? 20,
                'is_active' => $request->is_active ?? true,
                'created_by' => auth()->id(),
            ]);

            if ($request->has('bands')) {
                foreach ($request->bands as $bandData) {
                    $taxScheme->bands()->create($bandData);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tax scheme created successfully',
                'data' => $taxScheme->load('bands')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error creating tax scheme: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $taxScheme = TaxScheme::with('bands')->find($id);

        if (!$taxScheme) {
            return response()->json([
                'success' => false,
                'message' => 'Tax scheme not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $taxScheme
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $taxScheme = TaxScheme::find($id);

        if (!$taxScheme) {
            return response()->json([
                'success' => false,
                'message' => 'Tax scheme not found'
            ], 404);
        }

        // Protect system defined
        if ($taxScheme->is_system_defined) {
            return response()->json([
                'success' => false,
                'message' => 'Statutory system-defined schemes cannot be edited directly. Please duplicate it instead.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'employee_pension_percentage' => 'required|numeric|min:0|max:100',
            'employer_pension_percentage' => 'required|numeric|min:0|max:100',
            'apply_cra' => 'boolean',
            'apply_rent_relief' => 'boolean',
            'rent_relief_max_amount' => 'nullable|numeric|min:0',
            'rent_relief_percentage' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
            'bands' => 'nullable|array',
            'bands.*.id' => 'nullable|exists:payroll_tax_bands,id',
            'bands.*.lower_limit' => 'required|numeric|min:0',
            'bands.*.upper_limit' => 'nullable|numeric|min:0',
            'bands.*.rate_percentage' => 'required|numeric|min:0|max:100',
            'bands.*.flat_amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $taxScheme->update([
                'name' => $request->name,
                'description' => $request->description,
                'employee_pension_percentage' => $request->employee_pension_percentage,
                'employer_pension_percentage' => $request->employer_pension_percentage,
                'apply_cra' => $request->apply_cra ?? false,
                'apply_rent_relief' => $request->apply_rent_relief ?? false,
                'rent_relief_max_amount' => $request->rent_relief_max_amount ?? $taxScheme->rent_relief_max_amount,
                'rent_relief_percentage' => $request->rent_relief_percentage ?? $taxScheme->rent_relief_percentage,
                'is_active' => $request->is_active ?? $taxScheme->is_active,
                'updated_by' => auth()->id(),
            ]);

            if ($request->has('bands')) {
                // Delete bands not in the request
                $incomingIds = collect($request->bands)->pluck('id')->filter()->toArray();
                $taxScheme->bands()->whereNotIn('id', $incomingIds)->delete();

                foreach ($request->bands as $bandData) {
                    if (isset($bandData['id'])) {
                        TaxBand::where('id', $bandData['id'])->update($bandData);
                    } else {
                        $taxScheme->bands()->create($bandData);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tax scheme updated successfully',
                'data' => $taxScheme->load('bands')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error updating tax scheme: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $taxScheme = TaxScheme::find($id);

        if (!$taxScheme) {
            return response()->json([
                'success' => false,
                'message' => 'Tax scheme not found'
            ], 404);
        }

        // Protect system defined
        if ($taxScheme->is_system_defined) {
            return response()->json([
                'success' => false,
                'message' => 'Statutory system-defined schemes cannot be deleted. Please duplicate it instead.'
            ], 403);
        }

        // Check if tied to pay groups
        if ($taxScheme->payGroups()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete tax scheme assigned to pay groups'
            ], 400);
        }

        $taxScheme->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tax scheme deleted successfully'
        ]);
    }
}
