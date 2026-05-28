<?php

namespace App\Http\Controllers\Payroll;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Payroll\BatchPayment;
use App\Models\Payroll\MonthlyPayment;
use App\Models\Payroll\MonthlyPaymentItem;
use App\Models\Payroll\AnnualSalaryStructure;
use App\Models\Hris\Department;
use Illuminate\Http\Request;
use App\Traits\HandlesApiErrors;
use Illuminate\Support\Facades\DB;

class PayrollAnalyticsController extends Controller
{
    use HandlesApiErrors;

    /**
     * Get payroll analytics data
     */
    public function index(Request $request)
    {
        try {
            $tenantId = $request->user()->tenant_id;

            // 1. Payroll Cost Trend (Last 12 months)
            $costTrend = BatchPayment::where('tenant_id', $tenantId)
                ->where('status', 'authorized')
                ->where(function ($query) {
                    $query->where('year', '>', now()->year - 1)
                        ->orWhere(function ($q) {
                            $q->where('year', now()->year - 1)
                                ->where('month', '>=', now()->month);
                        });
                })
                ->with(['monthlyPayments'])
                ->get()
                ->groupBy(function ($batch) {
                    return $batch->year . '-' . str_pad($batch->month, 2, '0', STR_PAD_LEFT);
                })
                ->map(function ($batches, $monthYear) {
                    $totalGross = 0;
                    $totalNet = 0;
                    $totalTax = 0;

                    foreach ($batches as $batch) {
                        $totalGross += $batch->monthlyPayments->sum('gross_salary');
                        $totalNet += $batch->monthlyPayments->sum('net_salary');
                        $totalTax += $batch->monthlyPayments->sum('tax_amount');
                    }

                    return [
                        'month' => date('M', strtotime($monthYear . '-01')),
                        'gross' => (float)$totalGross,
                        'net' => (float)$totalNet,
                        'tax' => (float)$totalTax,
                    ];
                })
                ->values();

            // 2. Earnings & Deductions Breakdown (Current Year)
            $breakdown = DB::table('payroll_monthly_payment_items as items')
                ->join('payroll_monthly_payments as payments', 'items.monthly_payment_id', '=', 'payments.id')
                ->join('payroll_batch_payments as batches', 'payments.batch_payment_id', '=', 'batches.id')
                ->join('payroll_salary_components as components', 'items.component_id', '=', 'components.id')
                ->where('batches.tenant_id', $tenantId)
                ->where('batches.status', 'authorized')
                ->where('batches.year', now()->year)
                ->select('components.name', 'components.type', DB::raw('SUM(items.amount) as total'))
                ->groupBy('components.name', 'components.type')
                ->get()
                ->map(function ($item) {
                    return [
                        'name' => $item->name,
                        'type' => $item->type,
                        'value' => (float)$item->total,
                    ];
                });

            // 3. Departmental Payroll Distribution (Last authorized month)
            $lastBatch = BatchPayment::where('tenant_id', $tenantId)
                ->where('status', 'authorized')
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->first();

            $deptDistribution = [];
            if ($lastBatch) {
                $deptDistribution = DB::table('payroll_monthly_payments as payments')
                    ->join('employees', 'payments.employee_id', '=', 'employees.id')
                    ->join('employee_employment_details as details', 'employees.id', '=', 'details.employee_id')
                    ->join('departments', 'details.department_id', '=', 'departments.id')
                    ->where('payments.batch_payment_id', $lastBatch->id)
                    ->select('departments.name', DB::raw('SUM(payments.gross_salary) as total_gross'))
                    ->groupBy('departments.name')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'name' => $item->name,
                            'value' => (float)$item->total_gross,
                        ];
                    });
            }

            // 4. Compensation Benchmarking (Average Annual Gross per Grade)
            $benchmarking = DB::table('payroll_annual_salary_structures as structures')
                ->join('employees', 'structures.employee_id', '=', 'employees.id')
                ->join('employee_employment_details as details', 'employees.id', '=', 'details.employee_id')
                ->join('positions', 'details.position_id', '=', 'positions.id')
                ->join('grades', 'positions.grade_id', '=', 'grades.id')
                ->where('structures.tenant_id', $tenantId)
                ->where('structures.status', 'active')
                ->select('grades.name as grade_name', DB::raw('AVG(structures.total_annual_gross) as average_gross'))
                ->groupBy('grades.name')
                ->get()
                ->map(function ($item) {
                    return [
                        'grade' => $item->grade_name,
                        'average' => (float)round($item->average_gross, 2),
                    ];
                });

            return ApiResponse::success([
                'cost_trend' => $costTrend,
                'component_breakdown' => $breakdown,
                'department_distribution' => $deptDistribution,
                'benchmarking' => $benchmarking,
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Fetching payroll analytics');
        }
    }
}
