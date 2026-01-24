<?php

namespace App\Http\Controllers\HRIS;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Hris\Employee;
use App\Models\Hris\EmployeeProfileCompleteness;
use Illuminate\Http\Request;
use App\Traits\HandlesApiErrors;

class DashboardController extends Controller
{
    use HandlesApiErrors;

    /**
     * Get dashboard summary data
     */
    public function summary(Request $request)
    {
        try {
            $user = $request->user();
            $employee = $user->employee;

            if (!$employee) {
                return ApiResponse::error('Employee profile not found', 404);
            }

            $employee->load([
                'employmentDetails.department',
                'employmentDetails.position.level',
                'employmentDetails.position.grade',
                'employmentDetails.manager'
            ]);

            // Profile Completeness
            $completeness = EmployeeProfileCompleteness::where('employee_id', $employee->id)->first();

            // Team (Same department) count
            $teamCount = Employee::whereHas('employmentDetails', function ($query) use ($employee) {
                $query->where('department_id', $employee->employmentDetails->department_id);
            })->count();

            // Downlines (Direct reports) count
            $downlinesCount = Employee::whereHas('employmentDetails', function ($query) use ($employee) {
                $query->where('manager_id', $employee->id);
            })->count();

            return ApiResponse::success([
                'employee' => $employee,
                'last_login' => $user->previous_login,
                'profile_completeness' => $completeness ? $completeness->overall_completion : 0,
                'stats' => [
                    'team_members' => $teamCount,
                    'direct_reports' => $downlinesCount,
                ]
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Fetching dashboard summary');
        }
    }

    /**
     * Get team members (employees in the same department)
     */
    public function team(Request $request)
    {
        try {
            $user = $request->user();
            $employee = $user->employee;

            if (!$employee || !$employee->employmentDetails) {
                return ApiResponse::success([]);
            }

            $departmentId = $employee->employmentDetails->department_id;

            $team = Employee::with([
                'employmentDetails.position.level',
                'employmentDetails.position.grade',
                'employmentDetails.department'
            ])
                ->whereHas('employmentDetails', function ($query) use ($departmentId) {
                    $query->where('department_id', $departmentId);
                })
                ->get();

            return ApiResponse::success($team);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Fetching team members');
        }
    }

    /**
     * Get downlines (direct reports)
     */
    public function downlines(Request $request)
    {
        try {
            $user = $request->user();
            $employee = $user->employee;

            if (!$employee) {
                return ApiResponse::success([]);
            }

            $downlines = Employee::with([
                'employmentDetails.position.level',
                'employmentDetails.position.grade',
                'employmentDetails.department'
            ])
                ->whereHas('employmentDetails', function ($query) use ($employee) {
                    $query->where('manager_id', $employee->id);
                })
                ->get();

            return ApiResponse::success($downlines);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Fetching direct reports');
        }
    }

    /**
     * Get user's recent notifications
     */
    public function notifications(Request $request)
    {
        try {
            $user = $request->user();

            // Get 7 most recent unread notifications
            $notifications = $user->unreadNotifications()
                ->latest()
                ->limit(7)
                ->get();

            // Format for frontend
            $formatted = $notifications->map(function ($notification) {
                // Laravel database notifications store data in a JSON 'data' column
                $data = $notification->data;

                return [
                    'id' => $notification->id,
                    'type' => $data['type'] ?? 'system',
                    'title' => $data['title'] ?? 'Notification',
                    'message' => $data['message'] ?? '',
                    'timestamp' => $notification->created_at->toISOString(),
                    'read' => !is_null($notification->read_at),
                    'action_url' => $data['action_url'] ?? null,
                    'action_text' => $data['action_text'] ?? null,
                ];
            });

            return ApiResponse::success($formatted);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Fetching notifications');
        }
    }

    /**
     * Get unread notification count
     */
    public function unreadCount(Request $request)
    {
        try {
            $count = $request->user()->unreadNotifications()->count();
            return ApiResponse::success(['count' => $count]);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Fetching unread count');
        }
    }

    /**
     * Mark a specific notification as read
     */
    public function markAsRead(Request $request, $id)
    {
        try {
            $notification = $request->user()->notifications()->findOrFail($id);
            $notification->markAsRead();

            return ApiResponse::success(['message' => 'Notification marked as read']);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Marking notification as read');
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request)
    {
        try {
            $request->user()->unreadNotifications->markAsRead();
            return ApiResponse::success(['message' => 'All notifications marked as read']);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Marking all notifications as read');
        }
    }

    /**
     * Get employees on probation
     */
    public function onProbation(Request $request)
    {
        try {
            $tenantId = $request->user()->tenant_id;
            $today = now()->startOfDay();

            $employees = Employee::with([
                'employmentDetails.position',
                'employmentDetails.department',
                'user'
            ])
                ->where('tenant_id', $tenantId)
                ->whereHas('employmentDetails', function ($query) {
                    $query->whereNotNull('probation_end_date')
                        ->where(function ($q) {
                            $q->whereNotIn('probation_status', ['passed', 'failed'])
                                ->orWhereNull('probation_status');
                        });
                })
                ->get()
                ->map(function ($employee) use ($today) {
                    $probationEndDate = \Carbon\Carbon::parse($employee->employmentDetails->probation_end_date)->startOfDay();
                    $employee->probation_end_date = $probationEndDate->format('Y-m-d');
                    // Calculate days remaining (ceil ensures even partial days count as 1 if we care about time, but with startOfDay it's clean)
                    $daysRemaining = $today->diffInDays($probationEndDate, false);
                    $employee->days_remaining = (int) $daysRemaining;

                    return $employee;
                })
                ->sortBy('days_remaining')
                ->values();

            return ApiResponse::success($employees);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Fetching employees on probation');
        }
    }

    /**
     * Get employees with birthdays this month
     */
    public function birthdaysThisMonth(Request $request)
    {
        try {
            $tenantId = $request->user()->tenant_id;
            $currentMonth = now()->month;
            $today = now()->startOfDay();

            $employees = Employee::with([
                'employmentDetails.position',
                'employmentDetails.department'
            ])
                ->where('tenant_id', $tenantId)
                ->whereNotNull('date_of_birth')
                ->whereRaw('MONTH(date_of_birth) = ?', [$currentMonth])
                ->get()
                ->map(function ($employee) use ($today) {
                    $birthDate = \Carbon\Carbon::parse($employee->date_of_birth);
                    $employee->age = $birthDate->age;
                    $employee->birth_date = $employee->date_of_birth;

                    // Check if birthday is today (compare month and day only)
                    $isBirthdayToday = $birthDate->month === $today->month && $birthDate->day === $today->day;
                    $employee->is_today = $isBirthdayToday;

                    return $employee;
                })
                ->sortBy(function ($employee) {
                    return \Carbon\Carbon::parse($employee->date_of_birth)->day;
                })
                ->sortByDesc('is_today')
                ->values();

            return ApiResponse::success($employees);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Fetching birthdays this month');
        }
    }

    /**
     * Get employees with work anniversaries this month
     */
    public function anniversariesThisMonth(Request $request)
    {
        try {
            $tenantId = $request->user()->tenant_id;
            $currentMonth = now()->month;
            $today = now()->startOfDay();

            $employees = Employee::with([
                'employmentDetails.position',
                'employmentDetails.department'
            ])
                ->where('tenant_id', $tenantId)
                ->whereHas('employmentDetails', function ($query) use ($currentMonth) {
                    $query->whereNotNull('hire_date')
                        ->whereRaw('MONTH(hire_date) = ?', [$currentMonth]);
                })
                ->get()
                ->map(function ($employee) use ($today) {
                    $hireDate = \Carbon\Carbon::parse($employee->employmentDetails->hire_date);
                    $employee->years_of_service = (int) $hireDate->diffInYears($today);
                    $employee->hire_date = $employee->employmentDetails->hire_date;

                    // Check if anniversary is today (compare month and day only)
                    $isAnniversaryToday = $hireDate->month === $today->month && $hireDate->day === $today->day;
                    $employee->is_today = $isAnniversaryToday;

                    return $employee;
                })
                ->sortBy(function ($employee) {
                    return \Carbon\Carbon::parse($employee->hire_date)->day;
                })
                ->sortByDesc('is_today')
                ->values();

            return ApiResponse::success($employees);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Fetching work anniversaries this month');
        }
    }

    /**
     * Get dashboard analytics data
     */
    public function analytics(Request $request)
    {
        try {
            $tenantId = $request->user()->tenant_id;

            // 1. Department Distribution
            $deptDistribution = \App\Models\Hris\Department::where('tenant_id', $tenantId)
                ->withCount(['employees' => function ($query) {
                    $query->where('is_active', true);
                }])
                ->get()
                ->map(function ($dept) {
                    return [
                        'id' => $dept->id,
                        'name' => $dept->name,
                        'value' => $dept->employees_count,
                    ];
                })
                ->filter(fn($item) => $item['value'] > 0)
                ->values();

            // 2. Gender Ratio
            $genderRatio = Employee::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->selectRaw('gender, count(*) as value')
                ->groupBy('gender')
                ->get()
                ->map(function ($item) {
                    return [
                        'name' => ucfirst($item->gender ?? 'unspecified'),
                        'value' => $item->value,
                    ];
                });

            // 3. Age Demographics
            $rawAgeData = Employee::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->whereNotNull('date_of_birth')
                ->selectRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) AS age')
                ->get();

            $ageBuckets = [
                '18-25' => 0,
                '26-35' => 0,
                '36-45' => 0,
                '46-55' => 0,
                '56+' => 0,
            ];

            foreach ($rawAgeData as $row) {
                $age = $row->age;
                if ($age >= 18 && $age <= 25) $ageBuckets['18-25']++;
                elseif ($age >= 26 && $age <= 35) $ageBuckets['26-35']++;
                elseif ($age >= 36 && $age <= 45) $ageBuckets['36-45']++;
                elseif ($age >= 46 && $age <= 55) $ageBuckets['46-55']++;
                elseif ($age >= 56) $ageBuckets['56+']++;
            }

            $ageDemographics = collect($ageBuckets)->map(function ($count, $range) {
                return ['range' => $range, 'count' => $count];
            })->values();

            // 4. Diversity - Nationality
            $nationalityDistribution = Employee::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->selectRaw('nationality, count(*) as count')
                ->groupBy('nationality')
                ->orderByDesc('count')
                ->get()
                ->map(function ($item) {
                    return [
                        'label' => $item->nationality ?? 'Other',
                        'count' => $item->count,
                    ];
                });

            // 5. Diversity - Education (Highest Degree)
            $educationDistribution = \App\Models\Hris\EmployeeEducation::where('tenant_id', $tenantId)
                ->whereHas('employee', function ($q) {
                    $q->where('is_active', true);
                })
                ->where('is_highest', true)
                ->selectRaw('degree, count(*) as count')
                ->groupBy('degree')
                ->orderByDesc('count')
                ->get()
                ->map(function ($item) {
                    return [
                        'label' => $item->degree ?? 'Other',
                        'count' => $item->count,
                    ];
                });

            // --- Phase 2: Trends & Performance ---

            // 6. Headcount Trend (Last 12 Months)
            $headcountTrend = [];
            for ($i = 11; $i >= 0; $i--) {
                $date = now()->subMonths($i)->endOfMonth();
                $count = Employee::where('tenant_id', $tenantId)
                    ->whereHas('employmentDetails', function ($query) use ($date) {
                        $query->where('hire_date', '<=', $date)
                            ->where(function ($q) use ($date) {
                                $q->whereNull('termination_date')
                                    ->orWhere('termination_date', '>', $date);
                            });
                    })
                    ->count();

                $headcountTrend[] = [
                    'month' => $date->format('M'),
                    'count' => $count,
                ];
            }

            // 7. Average Tenure (Years)
            $tenureData = Employee::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->whereHas('employmentDetails', function ($q) {
                    $q->whereNotNull('hire_date');
                })
                ->with('employmentDetails')
                ->get();

            $totalTenureMonths = $tenureData->sum(function ($emp) {
                return $emp->employmentDetails->hire_date->diffInMonths(now());
            });

            $averageTenure = $tenureData->count() > 0
                ? round(($totalTenureMonths / $tenureData->count()) / 12, 1)
                : 0;

            // 8. Retention Rate (Approximate for selected year)
            $selectedYear = $request->get('year', now()->year);
            $startDate = \Carbon\Carbon::create($selectedYear, 1, 1)->startOfDay();
            $endDate = ($selectedYear == now()->year) ? now() : \Carbon\Carbon::create($selectedYear, 12, 31)->endOfDay();

            $activeNow = Employee::where('tenant_id', $tenantId)->where('is_active', true)->count();

            $terminatedInYear = Employee::where('tenant_id', $tenantId)
                ->whereHas('employmentDetails', function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('termination_date', [$startDate, $endDate]);
                })
                ->count();

            $retentionRate = ($activeNow + $terminatedInYear) > 0
                ? round(($activeNow / ($activeNow + $terminatedInYear)) * 100, 1)
                : 100;

            // 9. Skills Heatmap (Top 6 skills)
            $skillsDistribution = \App\Models\Hris\EmployeeSkill::where('tenant_id', $tenantId)
                ->with('skill:id,name')
                ->selectRaw('
                    skill_id, 
                    AVG(CASE 
                        WHEN proficiency_level = "beginner" THEN 1 
                        WHEN proficiency_level = "intermediate" THEN 3 
                        WHEN proficiency_level = "advanced" THEN 4 
                        WHEN proficiency_level = "expert" THEN 5 
                        ELSE 0 
                    END) as average_level
                ')
                ->groupBy('skill_id')
                ->orderByDesc('average_level')
                ->limit(6)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->skill_id,
                        'subject' => $item->skill->name ?? 'Unknown',
                        'A' => (float) $item->average_level,
                        'fullMark' => 5,
                    ];
                });

            return ApiResponse::success([
                'department_distribution' => $deptDistribution,
                'gender_ratio' => $genderRatio,
                'age_demographics' => $ageDemographics,
                'average_age' => (int) $rawAgeData->avg('age'),
                'total_active_employees' => Employee::where('tenant_id', $tenantId)->where('is_active', true)->count(),
                'total_inactive_employees' => Employee::where('tenant_id', $tenantId)->where('is_active', false)->count(),
                'diversity' => [
                    'nationality' => $nationalityDistribution,
                    'education' => $educationDistribution,
                ],
                'headcount_trend' => $headcountTrend,
                'average_tenure' => $averageTenure,
                'retention_rate' => $retentionRate,
                'terminated_ytd' => $terminatedInYear,
                'selected_year' => (int) $selectedYear,
                'skills_distribution' => $skillsDistribution,
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Fetching dashboard analytics');
        }
    }
}
