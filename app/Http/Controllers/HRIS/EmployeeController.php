<?php

namespace App\Http\Controllers\HRIS;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Hris\Employee;
use App\Models\Hris\EmployeeContactDetail;
use App\Models\Hris\EmployeeEmploymentDetail;
use App\Models\Hris\EmployeeOnboardingStatus;
use App\Models\Role;
use App\Models\User;
use App\Notifications\WelcomeEmployee;
use App\Services\FileUploadService;
use App\Services\ProfileCompletenessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeeController extends Controller
{
    protected $fileUploadService;
    protected $completenessService;
    protected $employeeNumberService;

    public function __construct(
        FileUploadService $fileUploadService,
        ProfileCompletenessService $completenessService,
        \App\Services\EmployeeNumberService $employeeNumberService
    ) {
        $this->fileUploadService = $fileUploadService;
        $this->completenessService = $completenessService;
        $this->employeeNumberService = $employeeNumberService;
    }
    /**
     * Display a listing of employees
     */
    public function index(Request $request)
    {
        $query = Employee::with([
            'user',
            'employmentDetails.department',
            'employmentDetails.position',
            'employmentDetails.leaveGroup'
        ])
            ->where('tenant_id', $request->user()->tenant_id);

        // Filter by department
        if ($request->filled('department_id')) {
            $query->whereHas('employmentDetails', function ($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        // Filter by grade
        if ($request->filled('grade_id')) {
            $query->whereHas('employmentDetails.position', function ($q) use ($request) {
                $q->where('grade_id', $request->grade_id);
            });
        }

        // Filter by level
        if ($request->filled('level_id')) {
            $query->whereHas('employmentDetails.position', function ($q) use ($request) {
                $q->where('level_id', $request->level_id);
            });
        }

        // Filter by position
        if ($request->filled('position_id')) {
            $query->whereHas('employmentDetails', function ($q) use ($request) {
                $q->where('position_id', $request->position_id);
            });
        }

        // Filter by skill
        if ($request->filled('skill_id')) {
            $query->whereHas('skills', function ($q) use ($request) {
                $q->where('skill_id', $request->skill_id);
            });
        }

        // Filter by gender
        if ($request->filled('gender')) {
            if ($request->gender === 'unspecified') {
                $query->where(function ($q) {
                    $q->whereNull('gender')->orWhere('gender', '');
                });
            } else {
                $query->where('gender', $request->gender);
            }
        }

        // Filter by nationality
        if ($request->filled('nationality')) {
            $query->where('nationality', $request->nationality);
        }

        // Filter by education degree
        if ($request->filled('education_degree')) {
            $query->whereHas('education', function ($q) use ($request) {
                $q->where('degree', $request->education_degree)
                    ->where('is_highest', true);
            });
        }

        // Filter by age range
        if ($request->filled('age_min') || $request->filled('age_max')) {
            $ageMin = $request->get('age_min', 0);
            $ageMax = $request->get('age_max', 150);
            $query->whereRaw("TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN ? AND ?", [$ageMin, $ageMax]);
        }

        // Filter by active status
        if ($request->has('is_active')) { // Keep has() for boolean/number 0
            $query->where('is_active', $request->is_active);
        }

        // Filter by termination year
        if ($request->filled('termination_year')) {
            $query->whereHas('employmentDetails', function ($q) use ($request) {
                $q->whereYear('termination_date', $request->termination_year);
            });
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('employee_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('email', 'like', "%{$search}%");
                    });
            });
        }

        $employees = $query->paginate($request->get('per_page', 15));

        return ApiResponse::success($employees);
    }

    /**
     * Store a newly created employee
     */
    public function store(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $validated = $request->validate([
            'employee_number' => 'nullable|string|max:50|unique:employees,employee_number',
            'first_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email', // Required for user account
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'marital_status' => 'nullable|in:single,married,divorced,widowed',
            'nationality' => 'nullable|string|max:100',
            'national_id' => 'nullable|string|max:100',
            'passport_number' => 'nullable|string|max:100',
            'photo' => 'nullable|string|max:255',
            'is_active' => 'boolean',

            // Employment details (nested)
            'employment_details' => 'nullable|array',
            'employment_details.work_email' => 'nullable|email',
            'employment_details.department_id' => 'nullable|exists:departments,id,tenant_id,' . $tenantId,
            'employment_details.position_id' => 'nullable|exists:positions,id,tenant_id,' . $tenantId,
            'employment_details.manager_id' => 'nullable|exists:employees,id,tenant_id,' . $tenantId,
            'employment_details.team_lead_id' => 'nullable|exists:employees,id,tenant_id,' . $tenantId,
            'employment_details.secondary_manager_id' => 'nullable|exists:employees,id,tenant_id,' . $tenantId,
            'employment_details.employment_type' => 'nullable|string',
            'employment_details.employment_status' => 'nullable|string',
            'employment_details.hire_date' => 'nullable|date',
            'employment_details.probation_end_date' => 'nullable|date',
            'employment_details.probation_status' => 'nullable|in:pending,passed,failed,extended',
            'employment_details.confirmation_date' => 'nullable|date',
            'employment_details.contract_start_date' => 'nullable|date',
            'employment_details.contract_end_date' => 'nullable|date',
            'employment_details.termination_date' => 'nullable|date',
            'employment_details.termination_type' => 'nullable|string',
            'employment_details.termination_reason' => 'nullable|string',
            'employment_details.notice_period_days' => 'nullable|integer',
            'employment_details.work_location' => 'nullable|string',
            'employment_details.work_schedule' => 'nullable|string',
            'employment_details.shift' => 'nullable|string',
            'employment_details.remote_work_eligible' => 'nullable|boolean',
            'employment_details.leave_group_id' => 'nullable|exists:leave_groups,id,tenant_id,' . $tenantId,
        ]);

        DB::beginTransaction();
        $email = $validated['email'];
        $isNewUserCreated = false;

        try {
            // Generate employee number if not provided
            if (empty($validated['employee_number'])) {
                $validated['employee_number'] = $this->employeeNumberService->generateNextNumber($tenantId);
            }

            // Generate temporary password
            $temporaryPassword = 'Temp' . rand(1000, 9999) . '!';

            // Create user account with tenant_id from auth
            $user = User::create([
                'name' => trim($validated['first_name'] . ' ' . ($validated['middle_name'] ?? '') . ' ' . $validated['last_name']),
                'email' => $validated['email'],
                'password' => bcrypt($temporaryPassword),
                'tenant_id' => $tenantId,
                'email_verified_at' => null, // User must verify email
            ]);
            $isNewUserCreated = true;

            // Assign default 'Employee' role
            $employeeRole = Role::where('tenant_id', $tenantId)
                ->where('slug', 'employee')
                ->first();

            if ($employeeRole) {
                $user->roles()->attach($employeeRole->id);
            }

            // Create employee and link to user
            $validated['user_id'] = $user->id;
            $validated['tenant_id'] = $tenantId;
            $validated['created_by'] = $request->user()->id;

            // Extract employment details before creating employee
            $employmentDetailsData = $validated['employment_details'] ?? null;
            unset($validated['employment_details']);

            // Remove email from employee data (it's in users table)
            unset($validated['email']);

            $employee = Employee::create($validated);

            // Create employment details if provided
            if ($employmentDetailsData && ! empty($employmentDetailsData)) {
                EmployeeEmploymentDetail::create(array_merge(
                    $employmentDetailsData,
                    [
                        'tenant_id' => $employee->tenant_id,
                        'employee_id' => $employee->id,
                    ]
                ));
            }

            // Create onboarding status record
            EmployeeOnboardingStatus::create([
                'tenant_id' => $employee->tenant_id,
                'employee_id' => $employee->id,
                'user_created' => true,
                'welcome_email_sent' => true,
                'welcome_email_sent_at' => now(),
                'password_reset_sent' => true,
                'password_reset_sent_at' => now(),
            ]);

            DB::commit();

            // Notify and calculate completeness (outside transaction to avoid delays/locking if notification is slow)
            $user->notify(new WelcomeEmployee(
                $user->name,
                $employee->employee_number,
                $temporaryPassword
            ));

            $this->completenessService->calculate($employee);

            return ApiResponse::created([
                'employee' => $employee->load(['user', 'employmentDetails.department', 'employmentDetails.position', 'employmentDetails.manager', 'employmentDetails.teamLead', 'employmentDetails.secondaryManager', 'employmentDetails.leaveGroup']),
            ], 'Employee and user account created successfully. A welcome email has been sent with instructions to set their password.');
        } catch (\Exception $e) {
            DB::rollBack();

            // Manual cleanup fallback
            if ($isNewUserCreated) {
                $this->manualCleanup($email, $tenantId);
            }

            Log::error('Employee creation failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return ApiResponse::serverError('Failed to create employee: ' . $e->getMessage());
        }
    }

    private function manualCleanup($email, $tenantId)
    {
        if (!$email) return;

        try {
            $user = User::where('tenant_id', $tenantId)->where('email', $email)->first();
            if ($user) {
                $employee = Employee::where('user_id', $user->id)->first();
                if ($employee) {
                    EmployeeEmploymentDetail::where('employee_id', $employee->id)->delete();
                    EmployeeOnboardingStatus::where('employee_id', $employee->id)->delete();
                    $employee->forceDelete();
                }
                $user->delete();
                Log::info("Manual cleanup performed for failed form-based creation: {$email}");
            }
        } catch (\Exception $e) {
            Log::error("Manual cleanup failed in Controller for {$email}: " . $e->getMessage());
        }
    }

    /**
     * Display the specified employee
     */
    public function show(Employee $employee)
    {
        $employee->load([
            'user',
            'employmentDetails.department',
            'employmentDetails.position',
            'employmentDetails.manager',
            'employmentDetails.leaveGroup',
            'contactDetails',
            'financialDetails',
            'medicalDetails',
            'addresses',
            'emergencyContacts',
            'dependents',
            'education',
            'workExperience',
            'skills.skill',
            'certifications',
            'documents.documentType',
            'profileCompleteness',
        ]);

        return ApiResponse::success($employee);
    }

    /**
     * Update the specified employee
     */
    public function update(Request $request, Employee $employee)
    {


        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'employee_number' => 'sometimes|required|string|max:50|unique:employees,employee_number,' . $employee->id,
            'first_name' => 'sometimes|required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'sometimes|required|string|max:100',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'marital_status' => 'nullable|in:single,married,divorced,widowed',
            'nationality' => 'nullable|string|max:100',
            'national_id' => 'nullable|string|max:100',
            'passport_number' => 'nullable|string|max:100',
            'photo' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);


        $validated['updated_by'] = Auth::id();

        $employee->update($validated);
        $this->completenessService->calculate($employee);

        return response()->json([
            'success' => true,
            'message' => 'Employee updated successfully',
            'data' => $employee,
        ]);
    }

    /**
     * Remove the specified employee
     */
    public function destroy(Employee $employee)
    {
        $employee->delete();

        return ApiResponse::success(null, 'Employee deleted successfully');
    }

    /**
     * Get employment details
     */
    public function employmentDetails(Employee $employee)
    {
        $employee->load('employmentDetails.department', 'employmentDetails.position', 'employmentDetails.manager', 'employmentDetails.leaveGroup');

        return ApiResponse::success($employee->employmentDetails);
    }

    /**
     * Get contact details
     */
    public function contactDetails(Employee $employee)
    {
        $employee->load('contactDetails');

        return ApiResponse::success($employee->contactDetails);
    }

    /**
     * Get financial details
     */
    public function financialDetails(Employee $employee)
    {
        $employee->load('financialDetails');

        return ApiResponse::success($employee->financialDetails);
    }

    /**
     * Get medical details
     */
    public function medicalDetails(Employee $employee)
    {
        $employee->load('medicalDetails');

        return ApiResponse::success($employee->medicalDetails);
    }

    /**
     * Get employee profile completeness
     */
    public function profileCompleteness(Employee $employee)
    {
        $this->completenessService->calculate($employee);
        $employee->load('profileCompleteness');

        return ApiResponse::success($employee->profileCompleteness);
    }

    /**
     * Get employee history
     */
    public function history(Employee $employee)
    {
        $employee->load('history');

        return ApiResponse::success($employee->history);
    }

    /**
     * Create employee employment details
     */
    public function createEmploymentDetails(Request $request, Employee $employee)
    {
        $tenantId = $request->user()->tenant_id;
        // Check if employment details already exist
        if ($employee->employmentDetails) {
            return response()->json([
                'success' => false,
                'message' => 'Employment details already exist. Use PUT to update.',
            ], 409);
        }

        $validated = $request->validate([
            'work_email' => 'nullable|email',
            'department_id' => 'nullable|exists:departments,id,tenant_id,' . $tenantId,
            'position_id' => 'nullable|exists:positions,id,tenant_id,' . $tenantId,
            'manager_id' => 'nullable|exists:employees,id,tenant_id,' . $tenantId,
            'team_lead_id' => 'nullable|exists:employees,id,tenant_id,' . $tenantId,
            'secondary_manager_id' => 'nullable|exists:employees,id,tenant_id,' . $tenantId,
            'employment_type' => 'nullable|string',
            'employment_status' => 'nullable|string',
            'hire_date' => 'nullable|date',
            'probation_end_date' => 'nullable|date',
            'probation_status' => 'nullable|string',
            'confirmation_date' => 'nullable|date',
            'contract_start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date',
            'termination_date' => 'nullable|date',
            'termination_type' => 'nullable|string',
            'termination_reason' => 'nullable|string',
            'notice_period_days' => 'nullable|integer',
            'work_location' => 'nullable|string',
            'work_schedule' => 'nullable|string',
            'shift' => 'nullable|string',
            'remote_work_eligible' => 'nullable|boolean',
            'leave_group_id' => 'nullable|exists:leave_groups,id,tenant_id,' . $tenantId,
        ]);

        $employmentDetails = EmployeeEmploymentDetail::create(array_merge(
            $validated,
            [
                'tenant_id' => $employee->tenant_id,
                'employee_id' => $employee->id,
            ]
        ));

        return response()->json([
            'success' => true,
            'message' => 'Employment details created successfully',
            'data' => $employmentDetails->load(['department', 'position', 'manager', 'teamLead', 'secondaryManager', 'leaveGroup']),
        ], 201);
    }

    /**
     * Create employee contact details
     */
    public function createContactDetails(Request $request, Employee $employee)
    {

        // Check if contact details already exist
        if ($employee->contactDetails) {
            return response()->json([
                'success' => false,
                'message' => 'Contact details already exist. Use PUT to update.',
            ], 409);
        }

        $validated = $request->validate([
            'personal_email' => 'nullable|email',
            'work_phone' => 'nullable|string',
            'mobile_phone' => 'nullable|string',
            'home_phone' => 'nullable|string',
            'whatsapp_number' => 'nullable|string',
            'linkedin_url' => 'nullable|url',
            'skype_id' => 'nullable|string',
            'other_contact' => 'nullable|string',
            'preferred_contact_method' => 'nullable|string',
        ]);

        $contactDetails = EmployeeContactDetail::create(array_merge(
            $validated,
            [
                'tenant_id' => $employee->tenant_id,
                'employee_id' => $employee->id,
            ]
        ));

        return response()->json([
            'success' => true,
            'message' => 'Contact details created successfully',
            'data' => $contactDetails,
        ], 201);
    }

    /**
     * Update employee employment details
     */
    public function updateEmploymentDetails(Request $request, Employee $employee)
    {
        $tenantId = $request->user()->tenant_id;
        $employmentDetails = $employee->employmentDetails;

        if (! $employmentDetails) {
            return response()->json([
                'success' => false,
                'message' => 'Employment details not found',
            ], 404);
        }

        $validated = $request->validate([
            'work_email' => 'nullable|email',
            'department_id' => 'nullable|exists:departments,id,tenant_id,' . $tenantId,
            'position_id' => 'nullable|exists:positions,id,tenant_id,' . $tenantId,
            'manager_id' => 'nullable|exists:employees,id,tenant_id,' . $tenantId,
            'team_lead_id' => 'nullable|exists:employees,id,tenant_id,' . $tenantId,
            'secondary_manager_id' => 'nullable|exists:employees,id,tenant_id,' . $tenantId,
            'employment_type' => 'nullable|string',
            'employment_status' => 'nullable|string',
            'hire_date' => 'nullable|date',
            'probation_end_date' => 'nullable|date',
            'probation_status' => 'nullable|string',
            'confirmation_date' => 'nullable|date',
            'contract_start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date',
            'termination_date' => 'nullable|date',
            'termination_type' => 'nullable|string',
            'termination_reason' => 'nullable|string',
            'notice_period_days' => 'nullable|integer',
            'work_location' => 'nullable|string',
            'work_schedule' => 'nullable|string',
            'shift' => 'nullable|string',
            'remote_work_eligible' => 'nullable|boolean',
            'leave_group_id' => 'nullable|exists:leave_groups,id,tenant_id,' . $tenantId,
        ]);

        $employmentDetails->update($validated);

        // Side-effect: If employment status is set to terminated, deactivate the employee account
        if (isset($validated['employment_status']) && $validated['employment_status'] === 'terminated') {
            $employee->update(['is_active' => false]);
        }

        $this->completenessService->calculate($employee);

        return response()->json([
            'success' => true,
            'message' => 'Employment details updated successfully',
            'data' => $employmentDetails->fresh(['department', 'position', 'manager', 'teamLead', 'secondaryManager', 'leaveGroup']),
        ]);
    }

    /**
     * Update employee contact details
     */
    public function updateContactDetails(Request $request, Employee $employee)
    {
        $contactDetails = $employee->contactDetails;

        if (! $contactDetails) {
            return response()->json([
                'success' => false,
                'message' => 'Contact details not found',
            ], 404);
        }

        $validated = $request->validate([
            'personal_email' => 'nullable|email',
            'work_phone' => 'nullable|string',
            'mobile_phone' => 'nullable|string',
            'home_phone' => 'nullable|string',
            'whatsapp_number' => 'nullable|string',
            'linkedin_url' => 'nullable|url',
            'skype_id' => 'nullable|string',
            'other_contact' => 'nullable|string',
            'preferred_contact_method' => 'nullable|string',
        ]);

        $contactDetails->update($validated);
        $this->completenessService->calculate($employee);

        return response()->json([
            'success' => true,
            'message' => 'Contact details updated successfully',
            'data' => $contactDetails->fresh(),
        ]);
    }

    /**
     * Update employee financial details
     */
    public function updateFinancialDetails(Request $request, Employee $employee)
    {
        $financialDetails = $employee->financialDetails;

        if (! $financialDetails) {
            return response()->json([
                'success' => false,
                'message' => 'Financial details not found',
            ], 404);
        }

        $validated = $request->validate([
            'bank_name' => 'nullable|string',
            'account_number' => 'nullable|string',
            'account_name' => 'nullable|string',
            'bank_branch' => 'nullable|string',
            'swift_code' => 'nullable|string',
            'tax_id' => 'nullable|string',
            'pension_number' => 'nullable|string',
            'health_insurance_number' => 'nullable|string',
            'social_security_number' => 'nullable|string',
            'current_salary' => 'nullable|numeric',
            'salary_currency' => 'nullable|string|size:3',
            'payment_frequency' => 'nullable|string',
        ]);

        $financialDetails->update($validated);
        $this->completenessService->calculate($employee);

        return response()->json([
            'success' => true,
            'message' => 'Financial details updated successfully',
            'data' => $financialDetails->fresh(),
        ]);
    }

    /**
     * Update employee medical details
     */
    public function updateMedicalDetails(Request $request, Employee $employee)
    {
        $medicalDetails = $employee->medicalDetails;

        if (! $medicalDetails) {
            return response()->json([
                'success' => false,
                'message' => 'Medical details not found',
            ], 404);
        }

        $validated = $request->validate([
            'blood_group' => 'nullable|string',
            'genotype' => 'nullable|string',
            'allergies' => 'nullable|string',
            'chronic_conditions' => 'nullable|string',
            'disabilities' => 'nullable|string',
            'emergency_medical_info' => 'nullable|string',
            'health_insurance_provider' => 'nullable|string',
            'health_insurance_number' => 'nullable|string',
            'health_insurance_expiry' => 'nullable|date',
        ]);

        $medicalDetails->update($validated);
        $this->completenessService->calculate($employee);

        return response()->json([
            'success' => true,
            'message' => 'Medical details updated successfully',
            'data' => $medicalDetails->fresh(),
        ]);
    }

    /**
     * Update employee photo
     */
    public function updatePhoto(Request $request, Employee $employee)
    {
        try {
            $request->validate([
                'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            // Delete old photo if exists
            if ($employee->photo) {
                // We need to extract the path from the URL if it's a local path
                // For now, let's just upload the new one.
                // Proper deletion would require knowing the driver.
            }

            $uploadResult = $this->fileUploadService->upload(
                $request->file('photo'),
                'employee-photos',
                [
                    'employee_id' => $employee->id,
                    'validation' => ['image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
                ]
            );

            $employee->update([
                'photo' => $uploadResult['url'],
                'updated_by' => Auth::id(),
            ]);

            $this->completenessService->calculate($employee);

            return ApiResponse::success([
                'photo_url' => $uploadResult['url'],
            ], 'Profile photo updated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::validationError('Validation failed', $e->errors());
        } catch (\Exception $e) {
            Log::error('Photo upload failed: ' . $e->getMessage());
            return ApiResponse::serverError('Failed to upload photo');
        }
    }

    /**
     * Get consolidated activity history for an employee and all their related models
     */
    public function auditLogs(Request $request, Employee $employee)
    {
        try {
            // Define all model types related to an employee that we want to track
            $employeeRelatedModels = [
                Employee::class,
                EmployeeEmploymentDetail::class,
                EmployeeContactDetail::class,
                \App\Models\Hris\EmployeeFinancialDetail::class,
                \App\Models\Hris\EmployeeMedicalDetail::class,
                \App\Models\Hris\EmployeeAddress::class,
                \App\Models\Hris\EmployeeEmergencyContact::class,
                \App\Models\Hris\EmployeeDependent::class,
                \App\Models\Hris\EmployeeEducation::class,
                \App\Models\Hris\EmployeeWorkExperience::class,
                \App\Models\Hris\EmployeeSkill::class,
                \App\Models\Hris\EmployeeCertification::class,
                \App\Models\Hris\EmployeeDocument::class,
            ];

            // Get IDs for all related models that are morphable to the employee
            $query = AuditLog::where(function ($q) use ($employee, $employeeRelatedModels) {
                // Directly related logs where auditable is the employee themselves
                $q->where('auditable_type', Employee::class)
                    ->where('auditable_id', $employee->id);

                // Logs for related models
                foreach ($employeeRelatedModels as $model) {
                    if ($model === Employee::class) {
                        continue;
                    }

                    $q->orWhere(function ($subQ) use ($model, $employee) {
                        $subQ->where('auditable_type', $model)
                            ->whereIn('auditable_id', function ($innerQ) use ($model, $employee) {
                                // Subquery to find IDs of related models belonging to this employee
                                $innerQ->select('id')
                                    ->from((new $model)->getTable())
                                    ->where('employee_id', $employee->id);
                            });
                    });
                }

                // Also include logs where the User ID itself is the actor or target
                if ($employee->user_id) {
                    $q->orWhere(function ($subQ) use ($employee) {
                        $subQ->where('auditable_type', User::class)
                            ->where('auditable_id', $employee->user_id);
                    });
                }
            })->with('user:id,name,email')
                ->orderBy('created_at', 'desc');

            if ($request->hasAny(['per_page', 'page'])) {
                $logs = $query->paginate($request->get('per_page', 15));
            } else {
                $logs = $query->get();
            }

            return ApiResponse::success($logs);
        } catch (\Exception $e) {
            Log::error('Audit logs fetch failed: ' . $e->getMessage(), [
                'employee_id' => $employee->id,
                'trace' => $e->getTraceAsString(),
            ]);
            return ApiResponse::serverError('An error occurred while fetching activity history', $e->getMessage());
        }
    }

    /**
     * Get users for dropdown (both active and inactive)
     */
    public function getUsersForDropdown(Request $request)
    {
        try {
            $tenantId = $request->user()->tenant_id;

            $users = Employee::with('user:id,email')
                ->where('tenant_id', $tenantId)
                ->whereNotNull('user_id')
                ->select('id', 'user_id', 'first_name', 'last_name', 'is_active')
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get()
                ->map(function ($employee) {
                    return [
                        'id' => $employee->user_id,
                        'name' => trim($employee->first_name . ' ' . $employee->last_name),
                        'email' => $employee->user?->email,
                        'is_active' => $employee->is_active,
                    ];
                })
                ->filter(function ($user) {
                    return !empty($user['email']);
                })
                ->values();

            return ApiResponse::success($users);
        } catch (\Exception $e) {
            Log::error('Users dropdown fetch failed: ' . $e->getMessage());
            return ApiResponse::serverError('Failed to fetch users for dropdown');
        }
    }

    /**
     * Get activity history for the currently authenticated user's employee profile
     */
    public function myAuditLogs(Request $request)

    {
        try {
            $user = Auth::user();
            $employee = $user->employee;

            if (!$employee) {
                return ApiResponse::notFound('Employee profile not found for this user');
            }

            return $this->auditLogs($request, $employee);
        } catch (\Exception $e) {
            return ApiResponse::serverError('An error occurred while fetching your activity history', $e->getMessage());
        }
    }
}
