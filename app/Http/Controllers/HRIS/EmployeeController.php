<?php

namespace App\Http\Controllers\HRIS;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmployeeController extends Controller
{
    /**
     * Display a listing of employees
     */
    public function index(Request $request)
    {
        $query = Employee::with(['user', 'employmentDetails.department', 'employmentDetails.position'])
            ->where('tenant_id', $request->user()->tenant_id);

        // Filter by department
        if ($request->has('department_id')) {
            $query->whereHas('employmentDetails', function ($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
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
        try {
            $validated = $request->validate([
                'employee_number' => 'required|string|max:50|unique:employees,employee_number',
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
            ]);

            // Generate temporary password
            $temporaryPassword = 'Temp' . rand(1000, 9999) . '!';

            // Create user account with tenant_id from auth
            $user = \App\Models\User::create([
                'name' => trim($validated['first_name'] . ' ' . ($validated['middle_name'] ?? '') . ' ' . $validated['last_name']),
                'email' => $validated['email'],
                'password' => bcrypt($temporaryPassword),
                'tenant_id' => $request->user()->tenant_id, // Use authenticated user's tenant
                'email_verified_at' => null, // User must verify email
            ]);

            // Create employee and link to user
            $validated['user_id'] = $user->id;
            $validated['tenant_id'] = $request->user()->tenant_id; // Use authenticated user's tenant
            $validated['created_by'] = auth()->id();

            // Remove email from employee data (it's in users table)
            unset($validated['email']);

            $employee = Employee::create($validated);

            // Send welcome notification (email + in-app)
            $user->notify(new \App\Notifications\WelcomeEmployee(
                $user->name,
                $employee->employee_number,
                $temporaryPassword
            ));

            return \App\Helpers\ApiResponse::created([
                'employee' => $employee->load('user'),
                'temporary_password' => $temporaryPassword,
                'note' => 'A welcome email has been sent to the employee with login instructions.',
            ], 'Employee and user account created successfully. Welcome email sent.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return \App\Helpers\ApiResponse::validationError('Validation failed', $e->errors());
        } catch (\Exception $e) {
            Log::error('Employee creation failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return \App\Helpers\ApiResponse::serverError('Failed to create employee. Please try again.');
        }
    }

    /**
     * Display the specified employee
     */
    public function show($id)
    {
        $employee = Employee::with([
            'user',
            'employmentDetails.department',
            'employmentDetails.position',
            'employmentDetails.manager',
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
        ])->findOrFail($id);

        return ApiResponse::success($employee);
    }

    /**
     * Update the specified employee
     */
    public function update(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);

        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'employee_number' => 'required|string|max:50|unique:employees,employee_number,' . $id,
            'first_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'required|string|max:100',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'marital_status' => 'nullable|in:single,married,divorced,widowed',
            'nationality' => 'nullable|string|max:100',
            'national_id' => 'nullable|string|max:100',
            'passport_number' => 'nullable|string|max:100',
            'photo' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $validated['updated_by'] = auth()->id();

        $employee->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Employee updated successfully',
            'data' => $employee,
        ]);
    }

    /**
     * Remove the specified employee
     */
    public function destroy($id)
    {
        $employee = Employee::findOrFail($id);
        $employee->delete();

        return ApiResponse::success(null, 'Employee deleted successfully');
    }

    /**
     * Get employment details
     */
    public function employmentDetails($id)
    {
        $employee = Employee::with('employmentDetails.department', 'employmentDetails.position', 'employmentDetails.manager')
            ->findOrFail($id);

        return ApiResponse::success($employee->employmentDetails);
    }

    /**
     * Get contact details
     */
    public function contactDetails($id)
    {
        $employee = Employee::with('contactDetails')->findOrFail($id);

        return ApiResponse::success($employee->contactDetails);
    }

    /**
     * Get financial details
     */
    public function financialDetails($id)
    {
        $employee = Employee::with('financialDetails')->findOrFail($id);

        return ApiResponse::success($employee->financialDetails);
    }

    /**
     * Get medical details
     */
    public function medicalDetails($id)
    {
        $employee = Employee::with('medicalDetails')->findOrFail($id);

        return ApiResponse::success($employee->medicalDetails);
    }

    /**
     * Get employee profile completeness
     */
    public function profileCompleteness($id)
    {
        $employee = Employee::with('profileCompleteness')->findOrFail($id);

        return ApiResponse::success($employee->profileCompleteness);
    }

    /**
     * Get employee history
     */
    public function history($id)
    {
        $employee = Employee::with('history')->findOrFail($id);

        return ApiResponse::success($employee->history);
    }

    /**
     * Create employee employment details
     */
    public function createEmploymentDetails(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);

        // Check if employment details already exist
        if ($employee->employmentDetails) {
            return response()->json([
                'success' => false,
                'message' => 'Employment details already exist. Use PUT to update.',
            ], 409);
        }

        $validated = $request->validate([
            'work_email' => 'nullable|email',
            'department_id' => 'nullable|exists:departments,id',
            'position_id' => 'nullable|exists:positions,id',
            'manager_id' => 'nullable|exists:employees,id',
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
        ]);

        $employmentDetails = \App\Models\EmployeeEmploymentDetail::create(array_merge(
            $validated,
            [
                'tenant_id' => $employee->tenant_id,
                'employee_id' => $employee->id,
            ]
        ));

        return response()->json([
            'success' => true,
            'message' => 'Employment details created successfully',
            'data' => $employmentDetails->load(['department', 'position', 'manager']),
        ], 201);
    }

    /**
     * Create employee contact details
     */
    public function createContactDetails(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);

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

        $contactDetails = \App\Models\EmployeeContactDetail::create(array_merge(
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
    public function updateEmploymentDetails(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);
        $employmentDetails = $employee->employmentDetails;

        if (!$employmentDetails) {
            return response()->json([
                'success' => false,
                'message' => 'Employment details not found',
            ], 404);
        }

        $employmentDetails->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Employment details updated successfully',
            'data' => $employmentDetails->fresh(['department', 'position', 'manager']),
        ]);
    }

    /**
     * Update employee contact details
     */
    public function updateContactDetails(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);
        $contactDetails = $employee->contactDetails;

        if (!$contactDetails) {
            return response()->json([
                'success' => false,
                'message' => 'Contact details not found',
            ], 404);
        }

        $contactDetails->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Contact details updated successfully',
            'data' => $contactDetails->fresh(),
        ]);
    }

    /**
     * Update employee financial details
     */
    public function updateFinancialDetails(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);
        $financialDetails = $employee->financialDetails;

        if (!$financialDetails) {
            return response()->json([
                'success' => false,
                'message' => 'Financial details not found',
            ], 404);
        }

        $financialDetails->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Financial details updated successfully',
            'data' => $financialDetails->fresh(),
        ]);
    }

    /**
     * Update employee medical details
     */
    public function updateMedicalDetails(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);
        $medicalDetails = $employee->medicalDetails;

        if (!$medicalDetails) {
            return response()->json([
                'success' => false,
                'message' => 'Medical details not found',
            ], 404);
        }

        $medicalDetails->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Medical details updated successfully',
            'data' => $medicalDetails->fresh(),
        ]);
    }
}
