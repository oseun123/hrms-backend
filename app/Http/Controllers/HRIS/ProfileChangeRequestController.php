<?php

namespace App\Http\Controllers\HRIS;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Hris\Employee;
use App\Models\Hris\ProfileChangeRequest;
use App\Models\Preference\ProfileApprovalSetting;
use App\Models\User;
use App\Notifications\ProfileChangeRequestSubmitted;
use App\Services\FileUploadService;
use App\Traits\HandlesApiErrors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class ProfileChangeRequestController extends Controller
{
    use HandlesApiErrors;

    protected $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Submit a change request
     */
    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            $employee = $user->employee;

            if (!$employee) {
                return ApiResponse::error('Employee profile not found', 404);
            }

            $request->validate([
                'section' => 'required|string',
                'action' => 'required|string|in:POST,PUT,DELETE',
                'proposed_data' => 'required|array',
                'notes' => 'nullable|string',
            ]);

            // Check if there's already a pending request for this section
            if (ProfileChangeRequest::hasPendingRequest($employee->id, $request->section)) {
                return ApiResponse::error('You have a pending approval request for this section', 409);
            }

            DB::beginTransaction();

            // Get current data based on section
            $currentData = $this->getCurrentData($employee, $request->section);

            // Create the change request
            $changeRequest = ProfileChangeRequest::create([
                'tenant_id' => $user->tenant_id,
                'employee_id' => $employee->id,
                'section' => $request->section,
                'action' => $request->action,
                'current_data' => $currentData,
                'proposed_data' => $request->proposed_data,
                'status' => 'pending',
                'submitted_at' => now(),
                'notes' => $request->notes,
            ]);

            // Notify all HR users
            $hrUsers = User::hrUsers($user->tenant_id);

            Notification::send($hrUsers, new ProfileChangeRequestSubmitted($changeRequest, $employee));

            DB::commit();

            return ApiResponse::created($changeRequest, 'Change request submitted for approval');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Failed to submit change request');
        }
    }

    /**
     * Get employee's own requests
     */
    public function myRequests(Request $request)
    {
        try {
            $user = Auth::user();
            $employee = $user->employee;

            if (!$employee) {
                return ApiResponse::error('Employee profile not found', 404);
            }

            $status = $request->query('status');
            $page = $request->query('page', 1);
            $perPage = $request->query('per_page', 15);

            $query = ProfileChangeRequest::where('employee_id', $employee->id)
                ->with('reviewer:id,name');

            if ($status) {
                $query->where('status', $status);
            }

            $requests = $query->orderBy('submitted_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            return ApiResponse::success($requests);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to fetch requests');
        }
    }

    /**
     * Cancel a pending request
     */
    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $employee = $user->employee;

            if (!$employee) {
                return ApiResponse::error('Employee profile not found', 404);
            }

            $changeRequest = ProfileChangeRequest::where('id', $id)
                ->where('employee_id', $employee->id)
                ->where('status', 'pending')
                ->first();

            if (!$changeRequest) {
                return ApiResponse::notFound('Pending request not found');
            }

            $changeRequest->delete();

            return ApiResponse::success(null, 'Request cancelled successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to cancel request');
        }
    }

    /**
     * Upload a temporary file for a change request
     */
    public function uploadTemp(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
            ]);

            $uploadResult = $this->fileUploadService->upload(
                $request->file('file'),
                'temp-uploads',
                [
                    'validation' => ['mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
                ]
            );

            return ApiResponse::success([
                'file_url' => $uploadResult['url'],
                'file_path' => $uploadResult['path'],
                'metadata' => $uploadResult['metadata'],
            ], 'File uploaded to temporary storage');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Temporary file upload');
        }
    }

    /**
     * Get current data for a section
     */
    private function getCurrentData(Employee $employee, string $section): ?array
    {
        $data = null;

        switch ($section) {
            case 'contact_details':
                $data = $employee->contactDetails?->toArray();
                break;
            case 'financial':
                $data = $employee->financialDetails?->toArray();
                break;
            case 'medical':
                $data = $employee->medicalDetails?->toArray();
                break;
            case 'addresses':
                $data = $employee->addresses?->toArray();
                break;
            case 'emergency_contacts':
                $data = $employee->emergencyContacts?->toArray();
                break;
            case 'education':
                $data = $employee->education?->toArray();
                break;
            case 'skills':
                $data = $employee->skills?->toArray();
                break;
            case 'work_experience':
                $data = $employee->workExperience?->toArray();
                break;
            case 'certifications':
                $data = $employee->certifications?->toArray();
                break;
            case 'dependents':
                $data = $employee->dependents?->toArray();
                break;
            case 'documents':
                $data = $employee->documents?->toArray();
                break;
        }

        return $data;
    }
}
