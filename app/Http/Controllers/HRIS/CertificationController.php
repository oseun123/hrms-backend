<?php

namespace App\Http\Controllers\HRIS;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\HRIS\StoreCertificationRequest;
use App\Http\Requests\HRIS\UpdateCertificationRequest;
use App\Models\Hris\Employee;
use App\Models\Hris\EmployeeCertification;
use App\Services\ProfileCompletenessService;
use App\Traits\HandlesApiErrors;

class CertificationController extends Controller
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
            return ApiResponse::success($employee->certifications);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Fetching certification records');
        }
    }

    public function store(StoreCertificationRequest $request, Employee $employee)
    {
        try {
            $certification = EmployeeCertification::create(array_merge(
                $request->validated(),
                [
                    'tenant_id' => $employee->tenant_id,
                    'employee_id' => $employee->id,
                ]
            ));

            $this->completenessService->calculate($employee);

            return ApiResponse::created($certification, 'Certification record created successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Certification record creation');
        }
    }

    public function update(UpdateCertificationRequest $request, Employee $employee, EmployeeCertification $certification)
    {
        try {
            if ($certification->employee_id !== $employee->id) {
                return ApiResponse::notFound('Certification record not found for this employee');
            }

            $certification->update($request->validated());
            $this->completenessService->calculate($employee);

            return ApiResponse::success($certification->fresh(), 'Certification record updated successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Certification record update');
        }
    }

    public function destroy(Employee $employee, EmployeeCertification $certification)
    {
        try {
            if ($certification->employee_id !== $employee->id) {
                return ApiResponse::notFound('Certification record not found for this employee');
            }

            $certification->delete();
            $this->completenessService->calculate($employee);

            return ApiResponse::success(null, 'Certification record deleted successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Certification record deletion');
        }
    }
}
