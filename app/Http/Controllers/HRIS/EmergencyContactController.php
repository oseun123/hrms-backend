<?php

namespace App\Http\Controllers\HRIS;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\HRIS\StoreEmergencyContactRequest;
use App\Http\Requests\HRIS\UpdateEmergencyContactRequest;
use App\Models\Hris\Employee;
use App\Models\Hris\EmployeeEmergencyContact;
use App\Services\ProfileCompletenessService;
use App\Traits\HandlesApiErrors;

class EmergencyContactController extends Controller
{
    use HandlesApiErrors;

    protected $completenessService;

    public function __construct(ProfileCompletenessService $completenessService)
    {
        $this->completenessService = $completenessService;
    }

    public function index(Employee $employee)
    {
        try {
            return ApiResponse::success($employee->emergencyContacts);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Fetching emergency contacts');
        }
    }

    public function store(StoreEmergencyContactRequest $request, Employee $employee)
    {
        try {
            if ($request->is_primary) {
                $employee->emergencyContacts()->update(['is_primary' => false]);
            }

            $contact = EmployeeEmergencyContact::create(array_merge(
                $request->validated(),
                [
                    'tenant_id' => $employee->tenant_id,
                    'employee_id' => $employee->id,
                ]
            ));

            $this->completenessService->calculate($employee);

            return ApiResponse::created($contact, 'Emergency contact created successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Emergency contact creation');
        }
    }

    public function update(UpdateEmergencyContactRequest $request, Employee $employee, EmployeeEmergencyContact $contact)
    {
        try {
            if ($contact->employee_id !== $employee->id) {
                return ApiResponse::notFound('Emergency contact not found for this employee');
            }

            if ($request->is_primary) {
                $employee->emergencyContacts()->where('id', '!=', $contact->id)->update(['is_primary' => false]);
            }

            $contact->update($request->validated());
            $this->completenessService->calculate($employee);

            return ApiResponse::success($contact->fresh(), 'Emergency contact updated successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Emergency contact update');
        }
    }

    public function destroy(Employee $employee, EmployeeEmergencyContact $contact)
    {
        try {
            if ($contact->employee_id !== $employee->id) {
                return ApiResponse::notFound('Emergency contact not found for this employee');
            }

            $contact->delete();
            $this->completenessService->calculate($employee);

            return ApiResponse::success(null, 'Emergency contact deleted successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Emergency contact deletion');
        }
    }
}
