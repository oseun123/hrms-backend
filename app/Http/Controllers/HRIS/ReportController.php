<?php

namespace App\Http\Controllers\HRIS;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Hris\Department;
use App\Models\Hris\Employee;
use App\Models\Hris\EmployeeDocument;
use App\Models\Hris\EmployeeProfileCompleteness;
use App\Models\Hris\EmployeeSkill;
use App\Models\User;
use App\Traits\HandlesApiErrors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    use HandlesApiErrors;

    /**
     * Get headcount summary report
     */
    public function headcountSummary(Request $request)
    {
        try {
            $tenantId = $request->user()->tenant_id;

            // Handle both old 'date' and new 'startDate'/'endDate' params
            if ($request->has('startDate') && $request->has('endDate')) {
                $startDate = $request->input('startDate');
                $endDate = $request->input('endDate');
            } else {
                $endDate = $request->input('date', now()->toDateString());
                $startDate = \Carbon\Carbon::parse($endDate)->startOfMonth()->toDateString();
            }

            // Snapshot headcount as of endDate
            $query = Employee::where('tenant_id', $tenantId)
                ->whereHas('employmentDetails', function ($q) use ($endDate) {
                    $q->where('hire_date', '<=', $endDate)
                        ->where(function ($sq) use ($endDate) {
                            $sq->whereNull('termination_date')
                                ->orWhere('termination_date', '>', $endDate);
                        });
                });

            $totalEmployees = (clone $query)->count();
            $activeStaff = (clone $query)->where('is_active', true)->count();
            $inactiveStaff = (clone $query)->where('is_active', false)->count();

            // New Hires within the period
            $newHiresPeriod = Employee::where('tenant_id', $tenantId)
                ->whereHas('employmentDetails', function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('hire_date', [$startDate, $endDate]);
                })->count();

            // Terminations within the period
            $terminationsPeriod = Employee::where('tenant_id', $tenantId)
                ->whereHas('employmentDetails', function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('termination_date', [$startDate, $endDate]);
                })->count();

            // Headcount by Employment Type as of endDate
            $byType = DB::table('employees')
                ->join('employee_employment_details', 'employees.id', '=', 'employee_employment_details.employee_id')
                ->where('employees.tenant_id', $tenantId)
                ->where('employees.deleted_at', null)
                ->where('employee_employment_details.hire_date', '<=', $endDate)
                ->where(function ($q) use ($endDate) {
                    $q->whereNull('employee_employment_details.termination_date')
                        ->orWhere('employee_employment_details.termination_date', '>', $endDate);
                })
                ->select('employment_type', DB::raw('count(*) as count'))
                ->groupBy('employment_type')
                ->get();

            return ApiResponse::success([
                'total' => $totalEmployees,
                'active' => $activeStaff,
                'inactive' => $inactiveStaff,
                'new_hires_period' => $newHiresPeriod,
                'terminations_period' => $terminationsPeriod,
                'by_type' => $byType
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Headcount summary report');
        }
    }

    /**
     * Get department headcount report
     */
    public function departmentHeadcount(Request $request)
    {
        try {
            $tenantId = $request->user()->tenant_id;

            // Handle both old 'date' and new 'startDate'/'endDate' params
            if ($request->has('startDate') && $request->has('endDate')) {
                $startDate = $request->input('startDate');
                $endDate = $request->input('endDate');
            } else {
                $endDate = $request->input('date', now()->toDateString());
                $startDate = \Carbon\Carbon::parse($endDate)->startOfMonth()->toDateString();
            }

            $department = $request->input('department');

            $query = Department::where('tenant_id', $tenantId);

            if ($department) {
                $query->where('name', $department);
            }

            $report = $query->withCount([
                'employees as total_staff' => function ($q) use ($endDate) {
                    $q->whereHas('employmentDetails', function ($sq) use ($endDate) {
                        $sq->where('hire_date', '<=', $endDate)
                            ->where(function ($ssq) use ($endDate) {
                                $ssq->whereNull('termination_date')
                                    ->orWhere('termination_date', '>', $endDate);
                            });
                    });
                },
                'employees as active' => function ($q) use ($endDate) {
                    $q->where('is_active', true)
                        ->whereHas('employmentDetails', function ($sq) use ($endDate) {
                            $sq->where('hire_date', '<=', $endDate)
                                ->where(function ($ssq) use ($endDate) {
                                    $ssq->whereNull('termination_date')
                                        ->orWhere('termination_date', '>', $endDate);
                                });
                        });
                },
                'employees as inactive' => function ($q) use ($endDate) {
                    $q->where('is_active', false)
                        ->whereHas('employmentDetails', function ($sq) use ($endDate) {
                            $sq->where('hire_date', '<=', $endDate)
                                ->where(function ($ssq) use ($endDate) {
                                    $ssq->whereNull('termination_date')
                                        ->orWhere('termination_date', '>', $endDate);
                                });
                        });
                },
                'employees as new_hires' => function ($q) use ($startDate, $endDate) {
                    $q->whereHas('employmentDetails', function ($sq) use ($startDate, $endDate) {
                        $sq->whereBetween('hire_date', [$startDate, $endDate]);
                    });
                },
                'employees as terminations' => function ($q) use ($startDate, $endDate) {
                    $q->whereHas('employmentDetails', function ($sq) use ($startDate, $endDate) {
                        $sq->whereBetween('termination_date', [$startDate, $endDate]);
                    });
                }
            ])
                ->get()
                ->map(function ($dept) {
                    return [
                        'key' => (string) $dept->id,
                        'department' => $dept->name,
                        'totalStaff' => $dept->total_staff,
                        'active' => $dept->active,
                        'inactive' => $dept->inactive,
                        'newHires' => $dept->new_hires,
                        'terminations' => $dept->terminations,
                    ];
                });

            return ApiResponse::success($report);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Department headcount report');
        }
    }

    /**
     * Get demographics report
     */
    public function demographics(Request $request)
    {
        try {
            $tenantId = $request->user()->tenant_id;
            $department = $request->input('department');
            $genderParam = $request->input('gender');
            $ageGroupParam = $request->input('ageGroup');

            $query = Employee::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->whereNotNull('date_of_birth');

            if ($department) {
                $query->whereHas('employmentDetails.department', function ($q) use ($department) {
                    $q->where('name', $department);
                });
            }

            if ($genderParam && $genderParam !== 'All') {
                $query->where('gender', strtolower($genderParam));
            }

            $rawAgeData = $query->selectRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) AS age, gender')
                ->get();

            $totalActive = $rawAgeData->count();

            $ageBuckets = [
                '18-25' => ['Male' => 0, 'Female' => 0, 'Other' => 0, 'Unspecified' => 0, 'total' => 0],
                '26-35' => ['Male' => 0, 'Female' => 0, 'Other' => 0, 'Unspecified' => 0, 'total' => 0],
                '36-45' => ['Male' => 0, 'Female' => 0, 'Other' => 0, 'Unspecified' => 0, 'total' => 0],
                '46-55' => ['Male' => 0, 'Female' => 0, 'Other' => 0, 'Unspecified' => 0, 'total' => 0],
                '56+' => ['Male' => 0, 'Female' => 0, 'Other' => 0, 'Unspecified' => 0, 'total' => 0],
            ];

            foreach ($rawAgeData as $row) {
                $age = $row->age;
                $gender = ucfirst($row->gender ?? 'unspecified');
                if ($gender !== 'Male' && $gender !== 'Female' && $gender !== 'Other') {
                    $gender = 'Unspecified';
                }

                $bucket = null;
                if ($age >= 18 && $age <= 25) $bucket = '18-25';
                elseif ($age >= 26 && $age <= 35) $bucket = '26-35';
                elseif ($age >= 36 && $age <= 45) $bucket = '36-45';
                elseif ($age >= 46 && $age <= 55) $bucket = '46-55';
                elseif ($age >= 56) $bucket = '56+';

                if ($bucket) {
                    $ageBuckets[$bucket][$gender]++;
                    $ageBuckets[$bucket]['total']++;
                }
            }

            $report = [];
            $i = 1;
            foreach ($ageBuckets as $range => $data) {
                // If ageGroup filter is set, only include that bucket
                if ($ageGroupParam && $ageGroupParam !== 'All' && $ageGroupParam !== $range) {
                    continue;
                }

                foreach (['Male', 'Female', 'Other', 'Unspecified'] as $gender) {
                    if ($data[$gender] > 0) {
                        $report[] = [
                            'key' => (string) ($i++),
                            'ageGroup' => $range,
                            'gender' => $gender,
                            'count' => $data[$gender],
                            'percentage' => $totalActive > 0 ? round(($data[$gender] / $totalActive) * 100, 1) . '%' : '0%',
                        ];
                    }
                }
            }

            return ApiResponse::success($report);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Demographics report');
        }
    }

    /**
     * Get employment report
     */
    public function employmentReport(Request $request)
    {
        try {
            $tenantId = $request->user()->tenant_id;
            $department = $request->input('department');
            $status = $request->input('status');

            $query = Employee::with([
                'employmentDetails.department',
                'employmentDetails.position',
                'employmentDetails.manager'
            ])
                ->where('tenant_id', $tenantId);

            if ($department) {
                $query->whereHas('employmentDetails.department', function ($q) use ($department) {
                    $q->where('name', $department);
                });
            }

            if ($status) {
                $isActive = (strtolower($status) === 'active');
                $query->where('is_active', $isActive);
            }

            $employees = $query->get()
                ->map(function ($emp) {
                    return [
                        'key' => (string) $emp->id,
                        'employeeNumber' => $emp->employee_number,
                        'name' => $emp->full_name,
                        'department' => $emp->employmentDetails?->department?->name ?? 'N/A',
                        'position' => $emp->employmentDetails?->position?->title ?? 'N/A',
                        'hireDate' => $emp->employmentDetails?->hire_date ? $emp->employmentDetails->hire_date->format('Y-m-d') : 'N/A',
                        'contractType' => $emp->employmentDetails?->employment_type ?? 'N/A',
                        'manager' => $emp->employmentDetails?->manager?->full_name ?? 'N/A',
                        'workSchedule' => $emp->employmentDetails?->work_schedule ?? 'N/A',
                        'probationEnd' => $emp->employmentDetails?->probation_end_date ? $emp->employmentDetails->probation_end_date->format('Y-m-d') : 'N/A',
                    ];
                });

            return ApiResponse::success($employees);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Employment report');
        }
    }

    /**
     * Get new hires report
     */
    public function newHires(Request $request)
    {
        try {
            $tenantId = $request->user()->tenant_id;
            $startDate = $request->input('startDate');
            $endDate = $request->input('endDate');
            $department = $request->input('department');
            $onboardingStatus = $request->input('onboardingStatus');

            $query = Employee::with(['employmentDetails.department', 'employmentDetails.position', 'onboardingStatus'])
                ->where('tenant_id', $tenantId);

            if ($startDate && $endDate) {
                $query->whereHas('employmentDetails', function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('hire_date', [$startDate, $endDate]);
                });
            } else {
                $days = $request->query('days', 30);
                $query->whereHas('employmentDetails', function ($q) use ($days) {
                    $q->where('hire_date', '>=', now()->subDays($days));
                });
            }

            if ($department) {
                $query->whereHas('employmentDetails.department', function ($q) use ($department) {
                    $q->where('name', $department);
                });
            }

            $report = $query->get()
                ->map(function ($emp) {
                    $onboarding = $emp->onboardingStatus;
                    $status = 'In Progress';
                    if ($onboarding && $onboarding->onboarding_completed) {
                        $status = 'Completed';
                    }

                    // Estimate completion rate based on onboarding flags
                    // Simplified: user_created, welcome_email_sent (includes reset), first_login, onboarding_completed
                    $completedFlags = 0;
                    $totalFlags = 4;
                    if ($onboarding) {
                        if ($onboarding->user_created) $completedFlags++;
                        if ($onboarding->welcome_email_sent) $completedFlags++; // Includes password_reset_sent
                        if ($onboarding->first_login_completed) $completedFlags++;
                        if ($onboarding->onboarding_completed) $completedFlags++;
                    }
                    $completionRate = round(($completedFlags / $totalFlags) * 100) . '%';

                    return [
                        'key' => (string) $emp->id,
                        'employeeNumber' => $emp->employee_number,
                        'name' => $emp->full_name,
                        'department' => $emp->employmentDetails?->department?->name ?? 'N/A',
                        'position' => $emp->employmentDetails?->position?->title ?? 'N/A',
                        'hireDate' => $emp->employmentDetails?->hire_date ? $emp->employmentDetails->hire_date->format('Y-m-d') : 'N/A',
                        'onboardingStatus' => $status,
                        'completionRate' => $completionRate,
                    ];
                });

            if ($onboardingStatus) {
                $report = $report->filter(function ($item) use ($onboardingStatus) {
                    return $item['onboardingStatus'] === $onboardingStatus;
                })->values();
            }

            return ApiResponse::success($report);
        } catch (\Exception $e) {
            return $this->handleException($e, 'New hires report');
        }
    }

    /**
     * Get attrition & turnover report
     */
    public function attrition(Request $request)
    {
        try {
            $tenantId = $request->user()->tenant_id;
            $startDate = $request->input('startDate');
            $endDate = $request->input('endDate');
            $departmentParam = $request->input('department');

            $report = [];

            if ($startDate && $endDate) {
                $start = \Carbon\Carbon::parse($startDate)->startOfMonth();
                $end = \Carbon\Carbon::parse($endDate)->endOfMonth();
            } else {
                $start = now()->subMonths(5)->startOfMonth();
                $end = now()->endOfMonth();
            }

            $current = $start->copy();
            while ($current->lte($end)) {
                $monthStart = $current->copy()->startOfMonth();
                $monthEnd = $current->copy()->endOfMonth();
                $monthLabel = $current->format('F Y');

                // Get departments for breakdown
                $departments = Department::where('tenant_id', $tenantId);
                if ($departmentParam) {
                    $departments->where('name', $departmentParam);
                }
                $departments = $departments->get();

                foreach ($departments as $dept) {
                    $separations = DB::table('employee_employment_details')
                        ->join('employees', 'employees.id', '=', 'employee_employment_details.employee_id')
                        ->where('employees.tenant_id', $tenantId)
                        ->where('employee_employment_details.department_id', $dept->id)
                        ->whereBetween('employee_employment_details.termination_date', [$monthStart, $monthEnd])
                        ->count();

                    $voluntaryExits = DB::table('employee_employment_details')
                        ->join('employees', 'employees.id', '=', 'employee_employment_details.employee_id')
                        ->where('employees.tenant_id', $tenantId)
                        ->where('employee_employment_details.department_id', $dept->id)
                        ->whereBetween('employee_employment_details.termination_date', [$monthStart, $monthEnd])
                        ->where('employee_employment_details.termination_type', 'Voluntary')
                        ->count();

                    $involuntaryExits = $separations - $voluntaryExits;

                    // Current headcount in that department at the end of THAT month
                    $headcount = DB::table('employee_employment_details')
                        ->join('employees', 'employees.id', '=', 'employee_employment_details.employee_id')
                        ->where('employees.tenant_id', $tenantId)
                        ->where('employee_employment_details.department_id', $dept->id)
                        ->where('employee_employment_details.hire_date', '<=', $monthEnd)
                        ->where(function ($query) use ($monthEnd) {
                            $query->whereNull('employee_employment_details.termination_date')
                                ->orWhere('employee_employment_details.termination_date', '>', $monthEnd);
                        })
                        ->count();

                    if ($separations > 0 || $headcount > 0) {
                        $attritionRate = $headcount > 0 ? round(($separations / $headcount) * 100, 1) . '%' : '0%';
                        $report[] = [
                            'key' => "attr-{$dept->id}-{$monthLabel}",
                            'month' => $monthLabel,
                            'department' => $dept->name,
                            'separations' => $separations,
                            'headcount' => $headcount,
                            'attritionRate' => $attritionRate,
                            'voluntaryExits' => $voluntaryExits,
                            'involuntaryExits' => $involuntaryExits,
                        ];
                    }
                }
                $current->addMonth();
            }

            return ApiResponse::success($report);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Attrition report');
        }
    }

    /**
     * Get document expiry report
     */
    public function documentExpiry(Request $request)
    {
        try {
            $tenantId = $request->user()->tenant_id;
            $startDate = $request->input('startDate');
            $endDate = $request->input('endDate');
            $documentType = $request->input('documentType');
            $statusParam = $request->input('status');

            $query = EmployeeDocument::with(['employee.employmentDetails.department', 'documentType'])
                ->where('tenant_id', $tenantId)
                ->whereNotNull('expiry_date');

            if ($startDate && $endDate) {
                $query->whereBetween('expiry_date', [$startDate, $endDate]);
            } else {
                $query->where('expiry_date', '>=', now());
            }

            if ($documentType) {
                $query->whereHas('documentType', function ($q) use ($documentType) {
                    $q->where('name', $documentType);
                });
            }

            $report = $query->orderBy('expiry_date', 'asc')
                ->get()
                ->map(function ($doc) {
                    $daysRemaining = now()->diffInDays($doc->expiry_date, false);
                    $status = 'Normal';
                    if ($daysRemaining < 30) $status = 'Critical';
                    elseif ($daysRemaining < 90) $status = 'Warning';

                    return [
                        'key' => (string) $doc->id,
                        'employeeNumber' => $doc->employee?->employee_number ?? 'N/A',
                        'name' => $doc->employee?->full_name ?? 'N/A',
                        'department' => $doc->employee?->employmentDetails?->department?->name ?? 'N/A',
                        'documentType' => $doc->documentType?->name ?? 'N/A',
                        'expiryDate' => $doc->expiry_date->format('Y-m-d'),
                        'daysRemaining' => (int) $daysRemaining,
                        'status' => $status,
                    ];
                });

            if ($statusParam) {
                $report = $report->filter(function ($item) use ($statusParam) {
                    return $item['status'] === $statusParam;
                })->values();
            }

            return ApiResponse::success($report);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Document expiry report');
        }
    }

    /**
     * Get profile completeness report
     */
    public function profileCompleteness(Request $request)
    {
        try {
            $tenantId = $request->user()->tenant_id;
            $department = $request->input('department');
            $threshold = $request->input('threshold');

            $query = Employee::with(['employmentDetails.department', 'profileCompleteness'])
                ->where('tenant_id', $tenantId)
                ->where('is_active', true);

            if ($department) {
                $query->whereHas('employmentDetails.department', function ($q) use ($department) {
                    $q->where('name', $department);
                });
            }

            $report = $query->get()
                ->map(function ($emp) {
                    $completeness = $emp->profileCompleteness;
                    $rate = $completeness ? (float) $completeness->overall_completion : 0;

                    // Identify missing major sections
                    $missing = [];
                    if ($completeness) {
                        if ($completeness->basic_info_completion < 100) $missing[] = 'Basic Info';
                        if ($completeness->employment_completion < 100) $missing[] = 'Employment';
                        if ($completeness->contact_completion < 100) $missing[] = 'Contact';
                        if ($completeness->financial_completion < 100) $missing[] = 'Financial';
                        if ($completeness->medical_completion < 100) $missing[] = 'Medical';
                    } else {
                        $missing = ['All Sections'];
                    }

                    return [
                        'key' => (string) $emp->id,
                        'employeeNumber' => $emp->employee_number,
                        'name' => $emp->full_name,
                        'department' => $emp->employmentDetails?->department?->name ?? 'N/A',
                        'completionRate' => round($rate),
                        'missingFields' => implode(', ', $missing),
                        'lastUpdated' => $completeness ? ($completeness->last_calculated_at ? $completeness->last_calculated_at->format('Y-m-d') : 'N/A') : 'N/A',
                    ];
                });

            if ($threshold) {
                $report = $report->filter(function ($item) use ($threshold) {
                    $rate = $item['completionRate'];
                    if ($threshold == 100) return $rate == 100;
                    if ($threshold == 75) return $rate >= 75;
                    if ($threshold == 50) return $rate >= 50;
                    if ($threshold == 25) return $rate < 50;
                    return true;
                })->values();
            }

            return ApiResponse::success($report);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Profile completeness report');
        }
    }

    /**
     * Get financials report
     */
    public function financials(Request $request)
    {
        try {
            $tenantId = $request->user()->tenant_id;
            $department = $request->input('department');

            $query = Employee::with(['employmentDetails.department', 'financialDetails'])
                ->where('tenant_id', $tenantId)
                ->where('is_active', true);

            if ($department) {
                $query->whereHas('employmentDetails.department', function ($q) use ($department) {
                    $q->where('name', $department);
                });
            }

            $report = $query->get()
                ->map(function ($emp) {
                    $fin = $emp->financialDetails;
                    return [
                        'key' => (string) $emp->id,
                        'employeeNumber' => $emp->employee_number,
                        'name' => $emp->full_name,
                        'department' => $emp->employmentDetails?->department?->name ?? 'N/A',
                        'bankName' => $fin?->bank_name ?? 'N/A',
                        'accountNumber' => $fin?->account_number ?? 'N/A',
                        'taxId' => $fin?->tax_id ?? 'N/A',
                        'pensionNumber' => $fin?->pension_number ?? 'N/A',
                        'paymentMethod' => $fin?->payment_method ?? 'N/A',
                    ];
                });

            return ApiResponse::success($report);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Financials report');
        }
    }

    /**
     * Get medical report
     */
    public function medical(Request $request)
    {
        try {
            $tenantId = $request->user()->tenant_id;
            $department = $request->input('department');
            $bloodGroup = $request->input('bloodGroup');

            $query = Employee::with(['employmentDetails.department', 'medicalDetails'])
                ->where('tenant_id', $tenantId)
                ->where('is_active', true);

            if ($department) {
                $query->whereHas('employmentDetails.department', function ($q) use ($department) {
                    $q->where('name', $department);
                });
            }

            if ($bloodGroup) {
                $query->whereHas('medicalDetails', function ($q) use ($bloodGroup) {
                    $q->where('blood_group', $bloodGroup);
                });
            }

            $report = $query->get()
                ->map(function ($emp) {
                    $med = $emp->medicalDetails;
                    return [
                        'key' => (string) $emp->id,
                        'employeeNumber' => $emp->employee_number,
                        'name' => $emp->full_name,
                        'department' => $emp->employmentDetails?->department?->name ?? 'N/A',
                        'bloodGroup' => $med?->blood_group ?? 'N/A',
                        'allergies' => $med?->allergies ?? 'N/A',
                        'medicalConditions' => $med?->chronic_conditions ?? 'N/A',
                        'insuranceProvider' => $med?->health_insurance_provider ?? 'N/A',
                    ];
                });

            return ApiResponse::success($report);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Medical report');
        }
    }

    /**
     * Get contact report
     */
    public function contact(Request $request)
    {
        try {
            $tenantId = $request->user()->tenant_id;
            $department = $request->input('department');
            $search = $request->input('search');

            $query = Employee::with(['employmentDetails.department', 'contactDetails', 'emergencyContacts'])
                ->where('tenant_id', $tenantId)
                ->where('is_active', true);

            if ($department) {
                $query->whereHas('employmentDetails.department', function ($q) use ($department) {
                    $q->where('name', $department);
                });
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhereHas('contactDetails', function ($sq) use ($search) {
                            $sq->where('personal_email', 'like', "%{$search}%")
                                ->orWhere('mobile_phone', 'like', "%{$search}%")
                                ->orWhere('work_phone', 'like', "%{$search}%")
                                ->orWhere('home_phone', 'like', "%{$search}%")
                                ->orWhere('whatsapp_number', 'like', "%{$search}%");
                        })
                        ->orWhereHas('emergencyContacts', function ($sq) use ($search) {
                            $sq->where('name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%")
                                ->orWhere('alternate_phone', 'like', "%{$search}%");
                        });
                });
            }

            $report = $query->get()
                ->map(function ($emp) {
                    $contact = $emp->contactDetails;
                    $primaryEmergency = $emp->emergencyContacts->where('is_primary', true)->first()
                        ?? $emp->emergencyContacts->first();

                    return [
                        'key' => (string) $emp->id,
                        'employeeNumber' => $emp->employee_number,
                        'name' => $emp->full_name,
                        'department' => $emp->employmentDetails?->department?->name ?? 'N/A',
                        'personalEmail' => $contact?->personal_email ?? 'N/A',
                        'mobilePhone' => $contact?->mobile_phone ?? 'N/A',
                        'emergencyContact' => $primaryEmergency?->name ?? 'N/A',
                        'emergencyPhone' => $primaryEmergency?->phone ?? 'N/A',
                    ];
                });

            return ApiResponse::success($report);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Contact report');
        }
    }

    /**
     * Get skills inventory report
     */
    public function skillsInventory(Request $request)
    {
        try {
            $tenantId = $request->user()->tenant_id;
            $departmentName = $request->input('department');
            $skillNameSearch = $request->input('search');
            $proficiencyLabels = $request->input('proficiency');

            $query = EmployeeSkill::where('tenant_id', $tenantId)
                ->with(['employee.employmentDetails.department', 'skill'])
                ->whereHas('employee', function ($q) {
                    $q->where('is_active', true);
                });

            if ($departmentName) {
                $query->whereHas('employee.employmentDetails.department', function ($q) use ($departmentName) {
                    $q->where('name', $departmentName);
                });
            }

            if ($skillNameSearch) {
                $query->whereHas('skill', function ($q) use ($skillNameSearch) {
                    $q->where('name', 'like', "%{$skillNameSearch}%");
                });
            }

            if ($proficiencyLabels) {
                $query->where('proficiency_level', $proficiencyLabels);
            }

            $report = $query->get()->map(function ($empSkill) {
                $emp = $empSkill->employee;
                return [
                    'key' => (string) $empSkill->id,
                    'employeeNumber' => $emp->employee_number ?? 'N/A',
                    'name' => $emp->full_name ?? 'N/A',
                    'department' => $emp->employmentDetails?->department?->name ?? 'N/A',
                    'skill' => $empSkill->skill?->name ?? 'N/A',
                    'proficiency' => $empSkill->proficiency_level ?? 'N/A',
                    'yearsOfExperience' => (float) $empSkill->years_of_experience,
                ];
            });

            return ApiResponse::success($report);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Skills inventory report');
        }
    }

    /**
     * Get birthday and anniversary report
     */
    public function birthdayAnniversary(Request $request)
    {
        try {
            $tenantId = $request->user()->tenant_id;
            $startDate = $request->input('startDate');
            $endDate = $request->input('endDate');
            $eventTypeParam = $request->input('eventType');
            $department = $request->input('department');

            if ($startDate && $endDate) {
                $start = \Carbon\Carbon::parse($startDate);
                $end = \Carbon\Carbon::parse($endDate);
            } else {
                $start = now()->startOfMonth();
                $end = now()->endOfMonth();
            }

            $query = Employee::with('employmentDetails.department')
                ->where('tenant_id', $tenantId)
                ->where('is_active', true);

            if ($department) {
                $query->whereHas('employmentDetails.department', function ($q) use ($department) {
                    $q->where('name', $department);
                });
            }

            $employees = $query->get();

            $report = [];

            foreach ($employees as $emp) {
                // Birthdays
                if ($emp->date_of_birth && (!$eventTypeParam || $eventTypeParam === 'Birthday')) {
                    $dob = $emp->date_of_birth->copy();

                    // Iterate through years between start and end date (usually same year)
                    $curr = $start->copy();
                    while ($curr->year <= $end->year) {
                        $thisYearDob = $dob->copy()->year($curr->year);
                        if ($thisYearDob->between($start, $end)) {
                            $report[] = [
                                'key' => "bd-{$emp->id}-" . $curr->format('Y'),
                                'employeeNumber' => $emp->employee_number,
                                'name' => $emp->full_name,
                                'department' => $emp->employmentDetails?->department?->name ?? 'N/A',
                                'eventType' => 'Birthday',
                                'eventDate' => $thisYearDob->format('Y-m-d'),
                                'eventYear' => $curr->year,
                                'age' => (int) $thisYearDob->diffInYears($dob, true),
                                'yearsOfService' => $emp->employmentDetails?->hire_date ? (int) $thisYearDob->diffInYears($emp->employmentDetails->hire_date, true) : 0,
                            ];
                        }
                        $curr->addYear();
                    }
                }

                // Anniversaries
                if ($emp->employmentDetails?->hire_date && (!$eventTypeParam || $eventTypeParam === 'Anniversary')) {
                    $hireDate = $emp->employmentDetails->hire_date->copy();

                    $curr = $start->copy();
                    while ($curr->year <= $end->year) {
                        $thisYearAnn = $hireDate->copy()->year($curr->year);
                        if ($thisYearAnn->between($start, $end)) {
                            $years = $thisYearAnn->diffInYears($hireDate);
                            if ($years > 0) {
                                $report[] = [
                                    'key' => "ann-{$emp->id}-" . $curr->format('Y'),
                                    'employeeNumber' => $emp->employee_number,
                                    'name' => $emp->full_name,
                                    'department' => $emp->employmentDetails?->department?->name ?? 'N/A',
                                    'eventType' => 'Anniversary',
                                    'eventDate' => $thisYearAnn->format('Y-m-d'),
                                    'eventYear' => $curr->year,
                                    'age' => $emp->date_of_birth ? (int) $thisYearAnn->diffInYears($emp->date_of_birth, true) : 'N/A',
                                    'yearsOfService' => (int) $years,
                                ];
                            }
                        }
                        $curr->addYear();
                    }
                }
            }

            // Sort by eventDate
            usort($report, function ($a, $b) {
                return $a['eventDate'] <=> $b['eventDate'];
            });

            return ApiResponse::success($report);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Birthday & Anniversary report');
        }
    }

    /**
     * Get audit trail report
     */
    public function auditTrail(Request $request)
    {
        try {
            $tenantId = $request->user()->tenant_id;
            $startDate = $request->input('startDate');
            $endDate = $request->input('endDate');
            $search = $request->input('search');
            $action = $request->input('action');

            $query = AuditLog::with('user')
                ->where('tenant_id', $tenantId);

            if ($startDate && $endDate) {
                $query->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            }

            if ($search) {
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('email', 'like', "%{$search}%");
                });
            }

            if ($action) {
                $query->where('event', strtolower($action));
            }

            $logs = $query->orderBy('created_at', 'desc')
                ->limit(500)
                ->get()
                ->map(function ($log) {
                    return [
                        'key' => (string) $log->id,
                        'timestamp' => $log->created_at->format('Y-m-d H:i:s'),
                        'user' => $log->user?->email ?? 'System',
                        'action' => ucfirst($log->event),
                        'entity' => str_replace(['App\\Models\\Hris\\', 'App\\Models\\'], '', $log->auditable_type) . " #{$log->auditable_id}",
                        'changes' => $this->formatAuditChanges($log),
                    ];
                });

            return ApiResponse::success($logs);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Audit trail report');
        }
    }

    /**
     * Helper to format audit changes
     */
    private function formatAuditChanges($log)
    {
        $changes = [];
        if ($log->event === 'updated') {
            $oldValues = $log->old_values ?? [];
            $newValues = $log->new_values ?? [];

            foreach ($newValues as $key => $value) {
                if (in_array($key, ['created_at', 'updated_at', 'deleted_at', 'tenant_id'])) continue;
                $oldValue = $oldValues[$key] ?? 'N/A';

                // Truncate long values
                $oldValue = is_string($oldValue) && strlen($oldValue) > 50 ? substr($oldValue, 0, 47) . '...' : $oldValue;
                $value = is_string($value) && strlen($value) > 50 ? substr($value, 0, 47) . '...' : $value;

                $changes[] = ucfirst($key) . ": $oldValue → $value";
            }
        } elseif ($log->event === 'created') {
            $changes[] = "New record created";
        } elseif ($log->event === 'deleted') {
            $changes[] = "Record deleted";
        }

        return empty($changes) ? 'No significant changes' : implode(', ', $changes);
    }

    /**
     * Export report data to CSV/Excel (Backend version)
     */
    public function export(Request $request, string $type)
    {
        try {
            $format = $request->query('format', 'csv');

            // Re-use logic to get data based on type
            $data = [];
            $filename = "report_{$type}";

            switch ($type) {
                case 'headcount-summary':
                    // This one is a summary object, not a table. 
                    // For export, we might want a flattened version.
                    $summary = $this->headcountSummary($request)->getData()->data;
                    $data = [
                        ['Category', 'Count', 'Percentage'],
                        ['Total', $summary->total, '100%'],
                        ['Active', $summary->active, round(($summary->active / max($summary->total, 1)) * 100, 1) . '%'],
                        ['Inactive', $summary->inactive, round(($summary->inactive / max($summary->total, 1)) * 100, 1) . '%'],
                    ];
                    break;

                case 'department-headcount':
                    $report = $this->departmentHeadcount($request)->getOriginal()['data'];
                    $data[] = ['Department', 'Total Staff', 'Active', 'Inactive'];
                    foreach ($report as $row) {
                        $data[] = [$row['department'], $row['totalStaff'], $row['active'], $row['inactive']];
                    }
                    break;

                case 'demographics':
                    $report = $this->demographics($request)->getOriginal()['data'];
                    $data[] = ['Age Group', 'Gender', 'Count', 'Percentage'];
                    foreach ($report as $row) {
                        $data[] = [$row['ageGroup'], $row['gender'], $row['count'], $row['percentage']];
                    }
                    break;

                case 'employment':
                    $report = $this->employmentReport($request)->getOriginal()['data'];
                    $data[] = ['Emp #', 'Name', 'Department', 'Position', 'Hire Date', 'Contract', 'Manager'];
                    foreach ($report as $row) {
                        $data[] = [
                            $row['employeeNumber'],
                            $row['name'],
                            $row['department'],
                            $row['position'],
                            $row['hireDate'],
                            $row['contractType'],
                            $row['manager']
                        ];
                    }
                    break;

                case 'new-hires':
                    $report = $this->newHires($request)->getOriginal()['data'];
                    $data[] = ['Emp #', 'Name', 'Department', 'Position', 'Hire Date', 'Onboarding Status', 'Completion %'];
                    foreach ($report as $row) {
                        $data[] = [$row['employeeNumber'], $row['name'], $row['department'], $row['position'], $row['hireDate'], $row['onboardingStatus'], $row['completionRate']];
                    }
                    break;

                case 'attrition':
                    $report = $this->attrition($request)->getOriginal()['data'];
                    $data[] = ['Month', 'Department', 'Separations', 'Headcount', 'Attrition Rate', 'Voluntary Exits', 'Involuntary Exits'];
                    foreach ($report as $row) {
                        $data[] = [$row['month'], $row['department'], $row['separations'], $row['headcount'], $row['attritionRate'], $row['voluntaryExits'], $row['involuntaryExits']];
                    }
                    break;

                case 'document-expiry':
                    $report = $this->documentExpiry($request)->getOriginal()['data'];
                    $data[] = ['Emp #', 'Name', 'Department', 'Document Type', 'Expiry Date', 'Days Remaining', 'Status'];
                    foreach ($report as $row) {
                        $data[] = [$row['employeeNumber'], $row['name'], $row['department'], $row['documentType'], $row['expiryDate'], $row['daysRemaining'], $row['status']];
                    }
                    break;

                case 'profile-completeness':
                    $report = $this->profileCompleteness($request)->getOriginal()['data'];
                    $data[] = ['Emp #', 'Name', 'Department', 'Completion %', 'Missing Sections', 'Last Updated'];
                    foreach ($report as $row) {
                        $data[] = [$row['employeeNumber'], $row['name'], $row['department'], $row['completionRate'] . '%', $row['missingFields'], $row['lastUpdated']];
                    }
                    break;

                case 'financials':
                    $report = $this->financials($request)->getOriginal()['data'];
                    $data[] = ['Emp #', 'Name', 'Department', 'Bank Name', 'Account #', 'Tax ID', 'Pension #', 'Payment Method'];
                    foreach ($report as $row) {
                        $data[] = [$row['employeeNumber'], $row['name'], $row['department'], $row['bankName'], $row['accountNumber'], $row['taxId'], $row['pensionNumber'], $row['paymentMethod']];
                    }
                    break;

                case 'medical':
                    $report = $this->medical($request)->getOriginal()['data'];
                    $data[] = ['Emp #', 'Name', 'Department', 'Blood Group', 'Allergies', 'Conditions', 'Insurance Provider'];
                    foreach ($report as $row) {
                        $data[] = [$row['employeeNumber'], $row['name'], $row['department'], $row['bloodGroup'], $row['allergies'], $row['medicalConditions'], $row['insuranceProvider']];
                    }
                    break;

                case 'contact':
                    $report = $this->contact($request)->getOriginal()['data'];
                    $data[] = ['Emp #', 'Name', 'Department', 'Personal Email', 'Mobile Phone', 'Emergency Contact', 'Emergency Phone'];
                    foreach ($report as $row) {
                        $data[] = [$row['employeeNumber'], $row['name'], $row['department'], $row['personalEmail'], $row['mobilePhone'], $row['emergencyContact'], $row['emergencyPhone']];
                    }
                    break;

                case 'skills-inventory':
                    $report = $this->skillsInventory($request)->getOriginal()['data'];
                    $data[] = ['Emp #', 'Name', 'Department', 'Skill', 'Proficiency', 'Experience (Years)'];
                    foreach ($report as $row) {
                        $data[] = [$row['employeeNumber'], $row['name'], $row['department'], $row['skill'], $row['proficiency'], $row['yearsOfExperience']];
                    }
                    break;

                case 'birthday-anniversary':
                    $report = $this->birthdayAnniversary($request)->getOriginal()['data'];
                    $data[] = ['Emp #', 'Name', 'Department', 'Event Type', 'Event Date', 'Age', 'Years of Service'];
                    foreach ($report as $row) {
                        $data[] = [$row['employeeNumber'], $row['name'], $row['department'], $row['eventType'], $row['eventDate'], $row['age'], $row['yearsOfService']];
                    }
                    break;

                case 'audit-trail':
                    $report = $this->auditTrail($request)->getOriginal()['data'];
                    $data[] = ['Timestamp', 'User', 'Action', 'Entity', 'Changes'];
                    foreach ($report as $row) {
                        $data[] = [$row['timestamp'], $row['user'], $row['action'], $row['entity'], $row['changes']];
                    }
                    break;

                default:
                    return ApiResponse::error('Invalid report type', 400);
            }

            if ($format === 'excel' && class_exists('\Maatwebsite\Excel\Facades\Excel')) {
                // This would be the maatwebsite implementation
                // return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\GenericExport($data), "{$filename}.xlsx");
            }

            // CSV Fallback/Default
            $callback = function () use ($data) {
                $file = fopen('php://output', 'w');
                foreach ($data as $row) {
                    fputcsv($file, $row);
                }
                fclose($file);
            };

            return response()->stream($callback, 200, [
                "Content-Type" => "text/csv",
                "Content-Disposition" => "attachment; filename=\"{$filename}.csv\"",
                "Pragma" => "no-cache",
                "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
                "Expires" => "0"
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Report export');
        }
    }
}
