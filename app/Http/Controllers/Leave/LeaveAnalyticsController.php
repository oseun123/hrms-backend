<?php

namespace App\Http\Controllers\Leave;

use App\Helpers\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Leave\LeaveRequest;
use App\Services\LeaveYearService;
use App\Traits\HandlesApiErrors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class LeaveAnalyticsController extends Controller
{
    use HandlesApiErrors;

    protected LeaveYearService $leaveYearService;

    public function __construct(LeaveYearService $leaveYearService)
    {
        $this->leaveYearService = $leaveYearService;
    }

    /**
     * Get dashboard-level leave statistics
     */
    public function dashboardStats()
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;

            // Get leave year boundaries from the service
            $currentLeaveYear = $this->leaveYearService->getCurrentLeaveYear($tenantId);
            $boundaries = $this->leaveYearService->getLeaveYearBoundaries($currentLeaveYear, $tenantId);

            $today = now()->format('Y-m-d');

            $pendingQuery = LeaveRequest::where('tenant_id', $tenantId)
                ->where('status', 'pending');

            $onLeaveQuery = LeaveRequest::where('tenant_id', $tenantId)
                ->where('status', 'approved')
                ->where('start_date', '<=', $today)
                ->where('end_date', '>=', $today);

            $totalDaysQuery = LeaveRequest::where('tenant_id', $tenantId)
                ->where('status', 'approved')
                ->whereBetween('start_date', [$boundaries['start'], $boundaries['end']]);

            $distributionQuery = LeaveRequest::where('tenant_id', $tenantId)
                ->where('status', 'approved')
                ->whereBetween('start_date', [$boundaries['start'], $boundaries['end']]);

            if (!$user->is_hr) {
                $employee = $user->employee;
                $empId = $employee ? $employee->id : 0;

                $pendingQuery->where('employee_id', $empId);
                $onLeaveQuery->where('employee_id', $empId);
                $totalDaysQuery->where('employee_id', $empId);
                $distributionQuery->where('employee_id', $empId);
            }

            // 1. Total Approved Days
            $totalDays = $totalDaysQuery->sum('duration_days');

            // 2. Pending Requests Count
            $pendingCount = $pendingQuery->count();

            // 3. Employees currently on leave
            $onLeaveCount = $onLeaveQuery->count();

            // 4. Top Leave Types (Distribution)
            $typeDistribution = $distributionQuery->with('leaveType')
                ->selectRaw('leave_type_id, sum(duration_days) as total_days')
                ->groupBy('leave_type_id')
                ->get()
                ->map(function ($item) {
                    return [
                        'name' => $item->leaveType->name ?? 'Unknown',
                        'value' => (float) $item->total_days,
                    ];
                });

            return ApiResponse::success([
                'total_days_this_year' => (float) $totalDays,
                'pending_requests' => $pendingCount,
                'currently_on_leave' => $onLeaveCount,
                'type_distribution' => $typeDistribution,
                'leave_year' => $this->leaveYearService->getLeaveYearLabel($currentLeaveYear, $tenantId),
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching dashboard leave stats');
        }
    }

    /**
     * Get monthly leave usage trends for the last 12 months
     */
    public function usage()
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;

            $data = [];
            for ($i = 11; $i >= 0; $i--) {
                $month = now()->subMonths($i);
                $monthName = $month->format('M');
                $year = $month->year;
                $monthNum = $month->month;

                $monthStart = $month->copy()->startOfMonth()->format('Y-m-d');
                $monthEnd = $month->copy()->endOfMonth()->format('Y-m-d');

                $countQuery = LeaveRequest::where('tenant_id', $tenantId)
                    ->where('status', 'approved')
                    ->whereBetween('start_date', [$monthStart, $monthEnd]);

                if (!$user->is_hr) {
                    $employee = $user->employee;
                    $countQuery->where('employee_id', $employee ? $employee->id : 0);
                }

                $count = $countQuery->sum('duration_days');

                $data[] = [
                    'month' => $monthName,
                    'days' => (float) $count,
                ];
            }

            return ApiResponse::success($data);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching leave usage analytics');
        }
    }

    /**
     * Get aggregated leave usage summary by type and department
     */
    public function usageSummary(Request $request)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $year = $request->get('year', now()->year);

            $boundaries = $this->leaveYearService->getLeaveYearBoundaries((int)$year, $tenantId);

            $query = LeaveRequest::where('leave_requests.tenant_id', $tenantId)
                ->where('leave_requests.status', 'approved')
                ->whereBetween('leave_requests.start_date', [$boundaries['start'], $boundaries['end']]);

            if ($request->has('department_id')) {
                $query->whereHas('employee.employmentDetails', function ($q) use ($request) {
                    $q->where('employee_employment_details.department_id', $request->department_id);
                });
            }

            // By Leave Type
            $byType = (clone $query)
                ->with('leaveType')
                ->selectRaw('leave_type_id, sum(duration_days) as total_days')
                ->groupBy('leave_type_id')
                ->get()
                ->map(function ($item) {
                    return [
                        'type' => $item->leaveType->name ?? 'Unknown',
                        'days' => (float) $item->total_days,
                    ];
                });

            // By Department
            $byDept = (clone $query)
                ->join('employees', 'leave_requests.employee_id', '=', 'employees.id')
                ->join('employee_employment_details', 'employees.id', '=', 'employee_employment_details.employee_id')
                ->join('departments', 'employee_employment_details.department_id', '=', 'departments.id')
                ->selectRaw('departments.name as department, sum(leave_requests.duration_days) as total_days')
                ->groupBy('departments.name')
                ->get();

            // By Month (Trends)
            $byMonth = [];
            for ($m = 1; $m <= 12; $m++) {
                $monthName = Carbon::create($year, $m, 1)->format('M');
                $monthBoundaries = [
                    'start' => Carbon::create($year, $m, 1)->startOfMonth()->format('Y-m-d'),
                    'end' => Carbon::create($year, $m, 1)->endOfMonth()->format('Y-m-d'),
                ];
                $monthDays = (clone $query)
                    ->whereBetween('leave_requests.start_date', [$monthBoundaries['start'], $monthBoundaries['end']])
                    ->sum('leave_requests.duration_days');

                $byMonth[] = [
                    'month' => $monthName,
                    'days' => (float) $monthDays,
                ];
            }

            // Approval Latency (Average hours from applied_at to final approval)
            // We use the last actioned_at from leave_approvals for each approved request
            $latencyBoundaries = $this->leaveYearService->getLeaveYearBoundaries((int)$year, $tenantId);

            $latencyQuery = LeaveRequest::where('leave_requests.tenant_id', $tenantId)
                ->where('leave_requests.status', 'approved')
                ->whereBetween('leave_requests.start_date', [$latencyBoundaries['start'], $latencyBoundaries['end']]);

            if ($request->has('department_id')) {
                $latencyQuery->whereHas('employee.employmentDetails', function ($q) use ($request) {
                    $q->where('employee_employment_details.department_id', $request->department_id);
                });
            }

            $latencyData = $latencyQuery->join('leave_approvals', 'leave_requests.id', '=', 'leave_approvals.leave_request_id')
                ->selectRaw('leave_requests.id, leave_requests.applied_at, MAX(leave_approvals.actioned_at) as final_approval_at')
                ->groupBy('leave_requests.id', 'leave_requests.applied_at')
                ->get();

            $totalHours = 0;
            $count = $latencyData->count();

            foreach ($latencyData as $item) {
                if ($item->applied_at && $item->final_approval_at) {
                    $applied = Carbon::parse($item->applied_at);
                    $approved = Carbon::parse($item->final_approval_at);
                    $totalHours += $applied->diffInHours($approved);
                }
            }

            $avgLatencyHours = $count > 0 ? round($totalHours / $count, 1) : 0;

            // Peak Conflicts (Future dates with most overlapping leaves)
            $peakConflicts = [];
            $futureLeaves = LeaveRequest::where('tenant_id', $tenantId)
                ->where('status', 'approved')
                ->where('end_date', '>=', now()->format('Y-m-d'))
                ->get();

            if ($request->has('department_id')) {
                $futureLeaves = $futureLeaves->filter(function ($r) use ($request) {
                    return $r->employee->employmentDetails->department_id == $request->department_id;
                });
            }

            $dateCounts = [];
            foreach ($futureLeaves as $leave) {
                $start = Carbon::parse($leave->start_date);
                $end = Carbon::parse($leave->end_date);

                for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                    if ($d->lt(now()->startOfDay())) continue;
                    $dateStr = $d->format('Y-m-d');
                    $dateCounts[$dateStr] = ($dateCounts[$dateStr] ?? 0) + 1;
                }
            }

            arsort($dateCounts);
            $topDates = array_slice($dateCounts, 0, 5, true);

            foreach ($topDates as $date => $count) {
                $peakConflicts[] = [
                    'date' => $date,
                    'count' => $count,
                ];
            }

            // Cancellation Stats
            $approvedCount = (clone $query)->where('status', 'approved')->count();
            $cancelledBoundaries = $this->leaveYearService->getLeaveYearBoundaries((int)$year, $tenantId);
            $cancelledCount = LeaveRequest::where('tenant_id', $tenantId)
                ->where('status', 'cancelled')
                ->whereBetween('start_date', [$cancelledBoundaries['start'], $cancelledBoundaries['end']]);

            if ($request->has('department_id')) {
                $cancelledCount->whereHas('employee.employmentDetails', function ($q) use ($request) {
                    $q->where('department_id', $request->department_id);
                });
            }
            $cancelledCount = $cancelledCount->count();

            $totalRequests = $approvedCount + $cancelledCount;
            $cancellationRatio = $totalRequests > 0 ? round(($cancelledCount / $totalRequests) * 100, 1) : 0;

            return ApiResponse::success([
                'by_type' => $byType,
                'by_dept' => $byDept,
                'by_month' => $byMonth,
                'avg_latency_hours' => $avgLatencyHours,
                'peak_conflicts' => $peakConflicts,
                'cancellation_stats' => [
                    'approved' => $approvedCount,
                    'cancelled' => $cancelledCount,
                    'ratio' => $cancellationRatio,
                ],
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching leave usage summary');
        }
    }

    /**
     * Get detailed leave request history with filters
     */
    public function historyReport(Request $request)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $query = LeaveRequest::with(['leaveType', 'employee.employmentDetails.department', 'approvals.approver.employee'])
                ->where('tenant_id', $tenantId);

            if ($request->has('department_id')) {
                $query->whereHas('employee.employmentDetails', function ($q) use ($request) {
                    $q->where('department_id', $request->department_id);
                });
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('leave_type_id')) {
                $query->where('leave_type_id', $request->leave_type_id);
            }

            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('start_date', [$request->start_date, $request->end_date]);
            }

            $requests = $query->orderBy('start_date', 'desc')->get();
            $mapped = $requests->map(function ($r) {
                $approvalChain = $r->approvals->map(function ($a) {
                    $approverName = $a->approver?->employee?->full_name ?? $a->approver?->name ?? 'N/A';
                    return [
                        'level' => $a->level,
                        'approver_name' => $approverName,
                        'status' => $a->status,
                        'actioned_at' => $a->actioned_at ? $a->actioned_at->format('Y-m-d H:i') : null,
                        'comments' => $a->comments
                    ];
                });

                $formattedChain = $r->approvals->map(function ($a) {
                    $status = ucfirst($a->status);
                    $name = $a->approver?->employee?->full_name ?? $a->approver?->name ?? 'N/A';
                    $date = $a->actioned_at ? ' on ' . $a->actioned_at->format('Y-m-d') : '';
                    return "L{$a->level}: {$status} ({$name}{$date})";
                })->join(', ');

                return [
                    'id' => $r->id,
                    'employee_number' => $r->employee->employee_number ?? 'N/A',
                    'employee_name' => $r->employee->full_name ?? 'N/A',
                    'department' => $r->employee->employmentDetails->department->name ?? 'N/A',
                    'leave_type' => $r->leaveType->name ?? 'N/A',
                    'start_date' => $r->start_date->format('Y-m-d'),
                    'end_date' => $r->end_date->format('Y-m-d'),
                    'duration' => $r->duration_days,
                    'status' => ucfirst($r->status),
                    'applied_at' => $r->applied_at ? $r->applied_at->format('Y-m-d') : 'N/A',
                    'approval_chain' => $approvalChain->toArray(),
                    'formatted_approval_chain' => $formattedChain ?: 'No approval stages recorded',
                ];
            });

            return ApiResponse::success($mapped);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching leave history report');
        }
    }

    /**
     * Get balance snapshot for all employees
     */
    public function balanceReport(Request $request)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $year = $request->get('year', date('Y'));

            $query = \App\Models\Leave\LeaveBalance::with(['leaveType', 'employee.employmentDetails.department'])
                ->where('tenant_id', $tenantId)
                ->where('year', $year);

            if ($request->has('department_id')) {
                $query->whereHas('employee.employmentDetails', function ($q) use ($request) {
                    $q->where('department_id', $request->department_id);
                });
            }

            $balances = $query->get();

            return ApiResponse::success($balances->map(function ($b) {
                return [
                    'employee_number' => $b->employee->employee_number ?? 'N/A',
                    'employee_name' => $b->employee->full_name ?? 'N/A',
                    'department' => $b->employee->employmentDetails->department->name ?? 'N/A',
                    'leave_type' => $b->leaveType->name ?? 'N/A',
                    'entitled' => $b->entitled,
                    'accrued' => $b->accrued,
                    'carried_forward' => $b->carried_forward,
                    'manual_adjustment' => $b->manual_adjustment,
                    'used' => $b->used,
                    'available' => $b->available_balance,
                ];
            }));
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching leave balance report');
        }
    }

    /**
     * Get leave liability snapshot with financial value
     */
    public function liabilityReport(Request $request)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $year = $request->get('year', date('Y'));

            $query = \App\Models\Leave\LeaveBalance::with(['leaveType', 'employee.employmentDetails.department', 'employee.financialDetail'])
                ->where('tenant_id', $tenantId)
                ->where('year', $year);

            if ($request->has('department_id')) {
                $query->whereHas('employee.employmentDetails', function ($q) use ($request) {
                    $q->where('department_id', $request->department_id);
                });
            }

            $balances = $query->get();

            return ApiResponse::success($balances->map(function ($b) {
                $salary = $b->employee->financialDetail->current_salary ?? 0;
                $currency = $b->employee->financialDetail->salary_currency ?? 'USD';

                // Estimate daily rate (base 260 working days per year for annual salary)
                $dailyRate = $salary > 0 ? $salary / 260 : 0;
                $liabilityValue = round($b->available_balance * $dailyRate, 2);

                return [
                    'employee_number' => $b->employee->employee_number ?? 'N/A',
                    'employee_name' => $b->employee->full_name ?? 'N/A',
                    'department' => $b->employee->employmentDetails->department->name ?? 'N/A',
                    'leave_type' => $b->leaveType->name ?? 'N/A',
                    'available_days' => $b->available_balance,
                    'daily_rate' => round($dailyRate, 2),
                    'liability_value' => $liabilityValue,
                    'currency' => $currency
                ];
            }));
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching leave liability report');
        }
    }

    /**
     * Get absenteeism pattern analysis
     */
    public function absenteeismPattern(Request $request)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $year = $request->get('year', date('Y'));

            $absentBoundaries = $this->leaveYearService->getLeaveYearBoundaries((int)$year, $tenantId);
            // Focus on Sick Leave or all short-term leaves (< 3 days)
            $query = LeaveRequest::with(['employee.employmentDetails.department', 'leaveType'])
                ->where('tenant_id', $tenantId)
                ->where('status', 'approved')
                ->whereBetween('start_date', [$absentBoundaries['start'], $absentBoundaries['end']]);

            if ($request->has('department_id')) {
                $query->whereHas('employee.employmentDetails', function ($q) use ($request) {
                    $q->where('department_id', $request->department_id);
                });
            }

            $requests = $query->get();

            // Group by employee to find patterns
            $patterns = $requests->groupBy('employee_id')->map(function ($empRequests) {
                $employee = $empRequests->first()->employee;

                $totalRequests = $empRequests->count();
                $shortTermRequests = $empRequests->filter(fn($r) => $r->duration_days <= 2)->count();

                // Check for "Monday/Friday" pattern
                $monFriCount = $empRequests->filter(function ($r) {
                    $startDay = Carbon::parse($r->start_date)->dayOfWeek;
                    $endDay = Carbon::parse($r->end_date)->dayOfWeek;
                    return in_array($startDay, [1, 5]) || in_array($endDay, [1, 5]);
                })->count();

                return [
                    'employee_number' => $employee->employee_number ?? 'N/A',
                    'employee_name' => $employee->full_name ?? 'N/A',
                    'department' => $employee->employmentDetails->department->name ?? 'N/A',
                    'total_requests' => $totalRequests,
                    'short_term_count' => $shortTermRequests,
                    'mon_fri_pattern_count' => $monFriCount,
                    'risk_level' => $totalRequests > 5 || $monFriCount > 3 ? 'High' : ($totalRequests > 3 ? 'Medium' : 'Low')
                ];
            })->values();

            return ApiResponse::success($patterns);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching absenteeism patterns');
        }
    }

    /**
     * Get detailed approver latency report
     */
    public function latencyReport(Request $request)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $year = $request->get('year', date('Y'));

            $latencyRepBoundaries = $this->leaveYearService->getLeaveYearBoundaries((int)$year, $tenantId);
            $requests = LeaveRequest::with(['employee.employmentDetails.department', 'approvals.approver.employee'])
                ->where('tenant_id', $tenantId)
                ->where('status', 'approved')
                ->whereBetween('applied_at', [$latencyRepBoundaries['start'], $latencyRepBoundaries['end']])
                ->get();

            $data = $requests->map(function ($r) {
                $finalApproval = $r->approvals->where('status', 'approved')->max('actioned_at');
                $latencyHours = 0;
                if ($r->applied_at && $finalApproval) {
                    $latencyHours = Carbon::parse($r->applied_at)->diffInHours(Carbon::parse($finalApproval));
                }

                return [
                    'employee_number' => $r->employee->employee_number ?? 'N/A',
                    'employee_name' => $r->employee->full_name ?? 'N/A',
                    'department' => $r->employee->employmentDetails->department->name ?? 'N/A',
                    'applied_at' => $r->applied_at->format('Y-m-d H:i'),
                    'final_approval_at' => $finalApproval ? Carbon::parse($finalApproval)->format('Y-m-d H:i') : 'N/A',
                    'latency_hours' => $latencyHours,
                    'latency_days' => round($latencyHours / 24, 1)
                ];
            });

            return ApiResponse::success($data);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching latency report');
        }
    }

    /**
     * Get detailed leave conflict report
     */
    public function conflictReport(Request $request)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $startDate = $request->get('start_date', now()->format('Y-m-d'));
            $endDate = $request->get('end_date', now()->addMonths(1)->format('Y-m-d'));

            $leaves = LeaveRequest::with(['employee.employmentDetails.department', 'leaveType'])
                ->where('tenant_id', $tenantId)
                ->where('status', 'approved')
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('start_date', [$startDate, $endDate])
                        ->orWhereBetween('end_date', [$startDate, $endDate]);
                })
                ->get();

            $dateConflicts = [];
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);

            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                $dateStr = $d->format('Y-m-d');
                $overlapping = $leaves->filter(function ($l) use ($d) {
                    return $d->between(Carbon::parse($l->start_date), Carbon::parse($l->end_date));
                });

                if ($overlapping->count() > 1) {
                    // Check if they are in the same department
                    $depts = $overlapping->groupBy(fn($l) => $l->employee->employmentDetails->department_id);
                    foreach ($depts as $deptId => $deptLeaves) {
                        if ($deptLeaves->count() > 1) {
                            $dateConflicts[] = [
                                'date' => $dateStr,
                                'department' => $deptLeaves->first()->employee->employmentDetails->department->name ?? 'N/A',
                                'employee_count' => $deptLeaves->count(),
                                'employees' => $deptLeaves->map(fn($l) => $l->employee->full_name)->join(', ')
                            ];
                        }
                    }
                }
            }

            return ApiResponse::success($dateConflicts);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching conflict report');
        }
    }

    /**
     * Get list of employees currently on leave
     */
    public function activeLeaves()
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $today = now()->format('Y-m-d');

            $leaves = LeaveRequest::with(['employee.employmentDetails.department', 'employee.employmentDetails.position', 'employee.user', 'leaveType'])
                ->where('tenant_id', $tenantId)
                ->where('status', 'approved')
                ->where('start_date', '<=', $today)
                ->where('end_date', '>=', $today)
                ->get();

            return ApiResponse::success($leaves->map(function ($r) {
                $employee = $r->employee;
                if ($employee) {
                    $employee->leave_info = [
                        'type' => $r->leaveType->name ?? 'Unknown',
                        'start_date' => $r->start_date->format('Y-m-d'),
                        'end_date' => $r->end_date->format('Y-m-d'),
                        'duration' => $r->duration_days,
                    ];
                }
                return $employee;
            })->filter());
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching active leaves');
        }
    }

    /**
     * Get leave calendar events for a specific month
     */
    public function calendar(Request $request)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $month = $request->get('month'); // Remove default
            $year = $request->get('year', now()->year);

            // Get start and end dates based on whether month is provided
            if ($month) {
                $startDate = Carbon::create($year, $month, 1)->startOfMonth();
                $endDate = Carbon::create($year, $month, 1)->endOfMonth();
            } else {
                $startDate = Carbon::create($year, 1, 1)->startOfYear();
                $endDate = Carbon::create($year, 12, 31)->endOfYear();
            }

            $query = LeaveRequest::with(['employee', 'leaveType'])
                ->where('tenant_id', $tenantId)
                ->whereIn('status', ['approved', 'pending'])
                ->where(function ($q) use ($startDate, $endDate) {
                    // Get leaves that overlap with the period
                    $q->whereBetween('start_date', [$startDate, $endDate])
                        ->orWhereBetween('end_date', [$startDate, $endDate])
                        ->orWhere(function ($q2) use ($startDate, $endDate) {
                            $q2->where('start_date', '<=', $startDate)
                                ->where('end_date', '>=', $endDate);
                        });
                });

            // Only filter by department if specified
            if ($request->has('department_id') && $request->department_id) {
                $query->whereHas('employee.employmentDetails', function ($q) use ($request) {
                    $q->where('department_id', $request->department_id);
                });
            }

            $leaves = $query->orderBy('start_date')->get();

            return ApiResponse::success($leaves);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching calendar events');
        }
    }
}
