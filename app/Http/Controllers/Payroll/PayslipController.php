<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Payroll\MonthlyPayment;
use Illuminate\Http\Request;

class PayslipController extends Controller
{
    /**
     * Get a list of the logged-in employee's payslips.
     */
    public function myPayslips(Request $request)
    {
        $employee = auth('sanctum')->user()->employee;

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee record not found for the current user'
            ], 404);
        }

        $payslips = MonthlyPayment::with('batchPayment')
            ->where('employee_id', $employee->id)
            ->whereHas('batchPayment', function ($query) {
                $query->where('status', 'authorized');
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $payslips
        ]);
    }

    /**
     * View specific payslip details.
     */
    public function show($id)
    {
        $employee = auth('sanctum')->user()->employee;

        $payslip = MonthlyPayment::with(['batchPayment.payGroup', 'items.component', 'employee'])
            ->where('id', $id)
            ->where('employee_id', $employee->id)
            ->first();

        if (!$payslip) {
            return response()->json([
                'success' => false,
                'message' => 'Payslip not found or unauthorized'
            ], 404);
        }

        if ($payslip->batchPayment->status !== 'authorized') {
            return response()->json([
                'success' => false,
                'message' => 'This payslip is still in draft and not available for viewing'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $payslip
        ]);
    }

    /**
     * Placeholder for PDF download.
     */
    public function download($id)
    {
        // Currently returns JSON. Would return PDF stream in a full implementation with dompdf.
        return $this->show($id);
    }
}
