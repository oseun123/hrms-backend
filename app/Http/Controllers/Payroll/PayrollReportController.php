<?php

namespace App\Http\Controllers\Payroll;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Payroll\BatchPayment;
use App\Models\Payroll\MonthlyPayment;
use App\Models\Payroll\MonthlyPaymentItem;
use App\Models\Payroll\AnnualSalaryStructure;
use App\Models\Hris\Department;
use App\Models\Hris\Employee;
use App\Models\Payroll\PayGroup;
use Illuminate\Http\Request;
use App\Traits\HandlesApiErrors;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PayrollReportController extends Controller
{
    use HandlesApiErrors;

    /**
     * Get monthly payroll summary report
     */
    public function monthlySummary(Request $request)
    {
        try {
            $tenantId = $request->user()->tenant_id;
            $month = $request->input('month', now()->month);
            $year = $request->input('year', now()->year);
            $departmentId = $request->input('department_id');
            $payGroupId = $request->input('pay_group_id');

            $query = MonthlyPayment::with(['employee', 'batchPayment.payGroup', 'items.component'])
                ->whereHas('batchPayment', function ($q) use ($tenantId, $month, $year, $payGroupId) {
                    $q->where('tenant_id', $tenantId)
                        ->where('status', 'authorized')
                        ->where('month', $month)
                        ->where('year', $year);
                    if ($payGroupId) {
                        $q->where('pay_group_id', $payGroupId);
                    }
                });

            if ($departmentId) {
                $query->whereHas('employee.employmentDetails', function ($q) use ($departmentId) {
                    $q->where('department_id', $departmentId);
                });
            }

            $report = $query->get()->map(function ($payment) {
                return [
                    'key' => (string)$payment->id,
                    'employee_number' => $payment->employee?->employee_number ?? 'N/A',
                    'employee_name' => $payment->employee?->full_name ?? 'N/A',
                    'department' => $payment->employee?->employmentDetails?->department?->name ?? 'N/A',
                    'pay_group' => $payment->batchPayment?->payGroup?->name ?? 'N/A',
                    'gross_salary' => (float)$payment->gross_salary,
                    'tax_amount' => (float)$payment->tax_amount,
                    'pension_ee' => (float)$payment->pension_ee,
                    'total_deductions' => (float)$payment->total_deductions,
                    'net_salary' => (float)$payment->net_salary,
                    'period' => date('F Y', strtotime($payment->batchPayment->year . '-' . $payment->batchPayment->month . '-01')),
                ];
            });

            return ApiResponse::success($report);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Monthly payroll summary report');
        }
    }

    /**
     * Get departmental expenditure report
     */
    public function departmentalExpenditure(Request $request)
    {
        try {
            $tenantId = $request->user()->tenant_id;
            $month = $request->input('month', now()->month);
            $year = $request->input('year', now()->year);

            $report = DB::table('payroll_monthly_payments as payments')
                ->join('employees', 'payments.employee_id', '=', 'employees.id')
                ->join('employee_employment_details as details', 'employees.id', '=', 'details.employee_id')
                ->join('departments', 'details.department_id', '=', 'departments.id')
                ->join('payroll_batch_payments as batches', 'payments.batch_payment_id', '=', 'batches.id')
                ->where('batches.tenant_id', $tenantId)
                ->where('batches.status', 'authorized')
                ->where('batches.month', $month)
                ->where('batches.year', $year)
                ->select(
                    'departments.name as department',
                    DB::raw('COUNT(payments.id) as staff_count'),
                    DB::raw('SUM(payments.gross_salary) as total_gross'),
                    DB::raw('SUM(payments.tax_amount) as total_tax'),
                    DB::raw('SUM(payments.pension_ee) as total_pension_ee'),
                    DB::raw('SUM(payments.pension_er) as total_pension_er'),
                    DB::raw('SUM(payments.net_salary) as total_net')
                )
                ->groupBy('departments.name')
                ->get();

            return ApiResponse::success($report);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Departmental expenditure report');
        }
    }

    /**
     * Get payroll variance report (current vs previous month)
     */
    public function varianceReport(Request $request)
    {
        try {
            $tenantId = $request->user()->tenant_id;
            $month = (int)$request->input('month', now()->month);
            $year = (int)$request->input('year', now()->year);

            $prevDate = Carbon::createFromDate($year, $month, 1)->subMonth();
            $prevMonth = $prevDate->month;
            $prevYear = $prevDate->year;

            $currentPayments = MonthlyPayment::whereHas('batchPayment', function ($q) use ($tenantId, $month, $year) {
                $q->where('tenant_id', $tenantId)->where('status', 'authorized')->where('month', $month)->where('year', $year);
            })->with('employee')->get()->keyBy('employee_id');

            $previousPayments = MonthlyPayment::whereHas('batchPayment', function ($q) use ($tenantId, $prevMonth, $prevYear) {
                $q->where('tenant_id', $tenantId)->where('status', 'authorized')->where('month', $prevMonth)->where('year', $prevYear);
            })->get()->keyBy('employee_id');

            $report = [];
            $allEmployeeIds = array_unique(array_merge($currentPayments->keys()->toArray(), $previousPayments->keys()->toArray()));

            foreach ($allEmployeeIds as $empId) {
                $curr = $currentPayments->get($empId);
                $prev = $previousPayments->get($empId);
                $emp = $curr ? $curr->employee : Employee::find($empId);

                $currGross = $curr ? (float)$curr->gross_salary : 0;
                $prevGross = $prev ? (float)$prev->gross_salary : 0;
                $variance = $currGross - $prevGross;
                $percent = $prevGross > 0 ? ($variance / $prevGross) * 100 : ($currGross > 0 ? 100 : 0);

                if ($variance != 0) {
                    $report[] = [
                        'key' => (string)$empId,
                        'employee_number' => $emp?->employee_number ?? 'N/A',
                        'employee_name' => $emp?->full_name ?? 'N/A',
                        'previous_gross' => $prevGross,
                        'current_gross' => $currGross,
                        'variance' => $variance,
                        'percentage' => round($percent, 2) . '%',
                    ];
                }
            }

            return ApiResponse::success($report);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Payroll variance report');
        }
    }

    /**
     * Get statutory compliance report (Tax & Pension)
     */
    public function statutoryCompliance(Request $request)
    {
        try {
            $tenantId = $request->user()->tenant_id;
            $month = $request->input('month', now()->month);
            $year = $request->input('year', now()->year);

            $report = MonthlyPayment::whereHas('batchPayment', function ($q) use ($tenantId, $month, $year) {
                $q->where('tenant_id', $tenantId)->where('status', 'authorized')->where('month', $month)->where('year', $year);
            })->with(['employee.financialDetails'])
                ->get()
                ->map(function ($payment) {
                    return [
                        'key' => (string)$payment->id,
                        'employee_name' => $payment->employee?->full_name,
                        'tax_id' => $payment->employee?->financialDetails?->tax_id ?? 'N/A',
                        'pension_number' => $payment->employee?->financialDetails?->pension_number ?? 'N/A',
                        'gross_salary' => (float)$payment->gross_salary,
                        'tax_amount' => (float)$payment->tax_amount,
                        'pension_ee' => (float)$payment->pension_ee,
                        'pension_er' => (float)$payment->pension_er,
                        'total_statutory' => (float)($payment->tax_amount + $payment->pension_ee + $payment->pension_er),
                    ];
                });

            return ApiResponse::success($report);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Statutory compliance report');
        }
    }

    /**
     * Get leave allowance reconciliation report
     */
    public function leaveAllowanceReconciliation(Request $request)
    {
        try {
            $tenantId = $request->user()->tenant_id;
            $year = $request->input('year', now()->year);

            $report = DB::table('payroll_monthly_payment_items as items')
                ->join('payroll_monthly_payments as payments', 'items.monthly_payment_id', '=', 'payments.id')
                ->join('payroll_batch_payments as batches', 'payments.batch_payment_id', '=', 'batches.id')
                ->join('payroll_salary_components as components', 'items.component_id', '=', 'components.id')
                ->join('employees', 'payments.employee_id', '=', 'employees.id')
                ->where('batches.tenant_id', $tenantId)
                ->where('batches.status', 'authorized')
                ->where('batches.year', $year)
                ->where('components.name', 'LIKE', '%Leave Allowance%')
                ->select(
                    'employees.employee_number',
                    'employees.first_name',
                    'employees.last_name',
                    'batches.month',
                    'batches.year',
                    'items.amount',
                    'batches.batch_name'
                )
                ->get()
                ->map(function ($row) {
                    return [
                        'key' => $row->employee_number . '-' . $row->month,
                        'employee' => $row->first_name . ' ' . $row->last_name,
                        'period' => date('F Y', strtotime($row->year . '-' . $row->month . '-01')),
                        'amount' => (float)$row->amount,
                        'batch' => $row->batch_name ?? 'Monthly Batch',
                    ];
                });

            return ApiResponse::success($report);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Leave allowance reconciliation report');
        }
    }

    /**
     * Get annual salary audit report
     */
    public function annualSalaryAudit(Request $request)
    {
        try {
            $tenantId = $request->user()->tenant_id;

            $report = AnnualSalaryStructure::with(['employee.employmentDetails.department', 'payGroup', 'wageItem'])
                ->where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->get()
                ->map(function ($s) {
                    return [
                        'key' => (string)$s->id,
                        'employee_number' => $s->employee?->employee_number ?? 'N/A',
                        'employee_name' => $s->employee?->full_name ?? 'N/A',
                        'department' => $s->employee?->employmentDetails?->department?->name ?? 'N/A',
                        'pay_group' => $s->payGroup?->name ?? 'N/A',
                        'template' => $s->wageItem?->name ?? 'Manual',
                        'annual_gross' => (float)$s->total_annual_gross,
                        'annual_net' => (float)$s->total_annual_net,
                    ];
                });

            return ApiResponse::success($report);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Annual salary audit report');
        }
    }
}
