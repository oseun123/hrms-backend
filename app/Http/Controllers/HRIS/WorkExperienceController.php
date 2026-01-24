<?php

namespace App\Http\Controllers\HRIS;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\HRIS\StoreWorkExperienceRequest;
use App\Http\Requests\HRIS\UpdateWorkExperienceRequest;
use App\Models\Hris\Employee;
use App\Models\Hris\EmployeeWorkExperience;
use App\Services\ProfileCompletenessService;
use App\Traits\HandlesApiErrors;

class WorkExperienceController extends Controller
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
            return ApiResponse::success($employee->workExperience);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Fetching work experience records');
        }
    }

    public function store(StoreWorkExperienceRequest $request, Employee $employee)
    {
        try {
            $experience = EmployeeWorkExperience::create(array_merge(
                $request->validated(),
                [
                    'tenant_id' => $employee->tenant_id,
                    'employee_id' => $employee->id,
                ]
            ));

            $this->completenessService->calculate($employee);

            return ApiResponse::created($experience, 'Work experience record created successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Work experience record creation');
        }
    }

    public function update(UpdateWorkExperienceRequest $request, Employee $employee, EmployeeWorkExperience $experience)
    {
        try {
            if ($experience->employee_id !== $employee->id) {
                return ApiResponse::notFound('Work experience record not found for this employee');
            }

            $experience->update($request->validated());
            $this->completenessService->calculate($employee);

            return ApiResponse::success($experience->fresh(), 'Work experience record updated successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Work experience record update');
        }
    }

    public function destroy(Employee $employee, EmployeeWorkExperience $experience)
    {
        try {
            if ($experience->employee_id !== $employee->id) {
                return ApiResponse::notFound('Work experience record not found for this employee');
            }

            $experience->delete();
            $this->completenessService->calculate($employee);

            return ApiResponse::success(null, 'Work experience record deleted successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Work experience record deletion');
        }
    }
}
