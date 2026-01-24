<?php

namespace App\Http\Controllers\HRIS;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Hris\Employee;
use App\Models\Hris\ProfileChangeRequest;
use App\Models\User;
use App\Models\Hris\EmployeeHistory;
use App\Notifications\ProfileChangeRequestApproved;
use App\Notifications\ProfileChangeRequestDeclined;
use App\Services\ProfileCompletenessService;
use App\Traits\HandlesApiErrors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HRApprovalQueueController extends Controller
{
    use HandlesApiErrors;

    protected $completenessService;

    public function __construct(ProfileCompletenessService $completenessService)
    {
        $this->completenessService = $completenessService;
    }

    /**
     * Get all pending approval requests (HR only)
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();



            $section = $request->query('section');
            $page = $request->query('page', 1);
            $perPage = $request->query('per_page', 15);

            $query = ProfileChangeRequest::with(['employee:id,employee_number,first_name,last_name,photo', 'reviewer:id,name'])
                ->where('tenant_id', $user->tenant_id)
                ->where('status', 'pending');

            if ($section) {
                $query->where('section', $section);
            }

            $requests = $query->orderBy('submitted_at', 'asc')
                ->paginate($perPage, ['*'], 'page', $page);

            return ApiResponse::success($requests);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to fetch approval queue');
        }
    }

    /**
     * Get single request details with side-by-side data
     */
    public function show($id)
    {
        try {
            $user = Auth::user();



            $request = ProfileChangeRequest::with(['employee:id,employee_number,first_name,last_name,photo', 'reviewer:id,name'])
                ->where('tenant_id', $user->tenant_id)
                ->find($id);

            if (!$request) {
                return ApiResponse::notFound('Request not found');
            }

            return ApiResponse::success($request);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to fetch request details');
        }
    }

    /**
     * Approve a request
     */
    public function approve($id)
    {
        try {
            $user = Auth::user();



            $changeRequest = ProfileChangeRequest::with('employee.user')
                ->where('tenant_id', $user->tenant_id)
                ->where('status', 'pending')
                ->find($id);

            if (!$changeRequest) {
                return ApiResponse::notFound('Pending request not found');
            }

            DB::beginTransaction();

            // Apply the changes to the employee's profile
            $this->applyChanges($changeRequest->employee, $changeRequest->section, $changeRequest->proposed_data);

            // Update request status
            $changeRequest->update([
                'status' => 'approved',
                'reviewed_at' => now(),
                'reviewed_by' => $user->id,
            ]);

            // Log to Employee History
            EmployeeHistory::create([
                'tenant_id' => $user->tenant_id,
                'employee_id' => $changeRequest->employee_id,
                'change_type' => 'profile_update',
                'effective_date' => now(),
                'previous_value' => $changeRequest->current_data,
                'new_value' => $changeRequest->proposed_data,
                'reason' => 'Profile change request approved',
                'notes' => "Approved section: {$changeRequest->section}. Submitted notes: {$changeRequest->notes}",
                'approved_by' => $user->id,
                'created_by' => $user->id,
            ]);

            // Notify employee
            $employeeUser = $changeRequest->employee->user;
            if ($employeeUser) {
                $employeeUser->notify(new ProfileChangeRequestApproved($changeRequest, $user));
            }

            $this->completenessService->calculate($changeRequest->employee);

            DB::commit();

            return ApiResponse::success(null, 'Request approved and changes applied');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Failed to approve request');
        }
    }

    /**
     * Decline a request
     */
    public function decline(Request $request, $id)
    {
        try {
            $user = Auth::user();



            $request->validate([
                'decline_reason' => 'required|string',
            ]);

            $changeRequest = ProfileChangeRequest::with('employee.user')
                ->where('tenant_id', $user->tenant_id)
                ->where('status', 'pending')
                ->find($id);

            if (!$changeRequest) {
                return ApiResponse::notFound('Pending request not found');
            }

            DB::beginTransaction();

            // Update request status
            $changeRequest->update([
                'status' => 'declined',
                'reviewed_at' => now(),
                'reviewed_by' => $user->id,
                'decline_reason' => $request->decline_reason,
            ]);

            // Notify employee
            $employeeUser = $changeRequest->employee->user;
            if ($employeeUser) {
                $employeeUser->notify(new ProfileChangeRequestDeclined($changeRequest, $user));
            }

            DB::commit();

            return ApiResponse::success(null, 'Request declined');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Failed to decline request');
        }
    }

    /**
     * Apply approved changes to employee profile
     */
    private function applyChanges(Employee $employee, string $section, array $proposedData): void
    {
        $tenantId = $employee->tenant_id;

        switch ($section) {
            case 'contact_details':
                $employee->contactDetails()->updateOrCreate(
                    ['employee_id' => $employee->id],
                    array_merge($proposedData, ['tenant_id' => $tenantId])
                );
                break;
            case 'financial':
                $employee->financialDetails()->updateOrCreate(
                    ['employee_id' => $employee->id],
                    array_merge($proposedData, ['tenant_id' => $tenantId])
                );
                break;
            case 'medical':
                $employee->medicalDetails()->updateOrCreate(
                    ['employee_id' => $employee->id],
                    array_merge($proposedData, ['tenant_id' => $tenantId])
                );
                break;
            case 'addresses':
                $employee->addresses()->delete();
                foreach ($proposedData as $address) {
                    $employee->addresses()->create(array_merge($address, ['tenant_id' => $tenantId]));
                }
                break;
            case 'emergency_contacts':
                $employee->emergencyContacts()->delete();
                foreach ($proposedData as $contact) {
                    $employee->emergencyContacts()->create(array_merge($contact, ['tenant_id' => $tenantId]));
                }
                break;
            case 'education':
                $employee->education()->delete();
                foreach ($proposedData as $edu) {
                    if (isset($edu['institution_name']) && !isset($edu['institution'])) {
                        $edu['institution'] = $edu['institution_name'];
                        unset($edu['institution_name']);
                    }
                    $employee->education()->create(array_merge($edu, ['tenant_id' => $tenantId]));
                }
                break;
            case 'work_experience':
                $employee->workExperience()->delete();
                foreach ($proposedData as $work) {
                    if (isset($work['description']) && !isset($work['responsibilities'])) {
                        $work['responsibilities'] = $work['description'];
                        unset($work['description']);
                    }
                    $employee->workExperience()->create(array_merge($work, ['tenant_id' => $tenantId]));
                }
                break;
            case 'skills':
                $employee->skills()->delete();
                foreach ($proposedData as $skill) {
                    if (isset($skill['last_used_date']) && !isset($skill['last_used'])) {
                        $skill['last_used'] = $skill['last_used_date'];
                        unset($skill['last_used_date']);
                    }
                    $employee->skills()->create(array_merge($skill, ['tenant_id' => $tenantId]));
                }
                break;
            case 'certifications':
                $employee->certifications()->delete();
                foreach ($proposedData as $cert) {
                    $employee->certifications()->create(array_merge($cert, ['tenant_id' => $tenantId]));
                }
                break;
            case 'dependents':
                $employee->dependents()->delete();
                foreach ($proposedData as $dep) {
                    $employee->dependents()->create(array_merge($dep, ['tenant_id' => $tenantId]));
                }
                break;
            case 'documents':
                $employee->documents()->delete();
                foreach ($proposedData as $doc) {
                    // Extract ID if missing but object present (legacy support for broken requests)
                    $documentTypeId = $doc['document_type_id'] ?? ($doc['document_type']['id'] ?? null);

                    // Filter out any frontend-only attributes like 'document_type' object
                    $validData = collect($doc)->only([
                        'document_name',
                        'file_path',
                        'file_url',
                        'file_size',
                        'mime_type',
                        'storage_driver',
                        'file_metadata',
                        'issue_date',
                        'expiry_date',
                        'notes'
                    ])->toArray();

                    $employee->documents()->create(array_merge($validData, [
                        'document_type_id' => $documentTypeId,
                        'tenant_id' => $tenantId,
                        'uploaded_by' => auth()->id() ?? $employee->user_id
                    ]));
                }
                break;
        }
    }
}
