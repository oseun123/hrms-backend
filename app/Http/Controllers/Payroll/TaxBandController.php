<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Payroll\TaxScheme;
use App\Models\Payroll\TaxBand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaxBandController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($scheme_id)
    {
        $scheme = TaxScheme::find($scheme_id);

        if (!$scheme) {
            return response()->json([
                'success' => false,
                'message' => 'Tax scheme not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $scheme->bands
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, $scheme_id)
    {
        $scheme = TaxScheme::find($scheme_id);

        if (!$scheme) {
            return response()->json([
                'success' => false,
                'message' => 'Tax scheme not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'lower_limit' => 'required|numeric|min:0',
            'upper_limit' => 'nullable|numeric|min:0',
            'rate_percentage' => 'required|numeric|min:0|max:100',
            'flat_amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $band = $scheme->bands()->create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Tax band added successfully',
            'data' => $band
        ], 201);
    }
}
