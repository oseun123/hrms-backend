<?php

namespace App\Http\Controllers\HRIS;

use App\Http\Controllers\Controller;
use App\Http\Requests\HRIS\StoreEducationRequest;
use App\Http\Requests\HRIS\UpdateEducationRequest;
use App\Models\Employee;
use App\Models\EmployeeEducation;
use App\Services\ProfileCompletenessService;
use App\Helpers\ApiResponse;
use App\Traits\HandlesApiErrors;

class EducationController extends Controller
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
            return ApiResponse::success($employee->education);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Fetching education records');
        }
    }

    public function store(StoreEducationRequest $request, Employee $employee)
    {
        try {
            if ($request->is_highest) {
                $employee->education()->update(['is_highest' => false]);
            }

            $education = EmployeeEducation::create(array_merge(
                $request->validated(),
                [
                    'tenant_id' => $employee->tenant_id,
                    'employee_id' => $employee->id,
                ]
            ));

            $this->completenessService->calculate($employee);

            return ApiResponse::created($education, 'Education record created successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Education record creation');
        }
    }

    public function update(UpdateEducationRequest $request, Employee $employee, EmployeeEducation $education)
    {
        try {
            if ($education->employee_id !== $employee->id) {
                return ApiResponse::notFound('Education record not found for this employee');
            }

            if ($request->is_highest) {
                $employee->education()->where('id', '!=', $education->id)->update(['is_highest' => false]);
            }

            $education->update($request->validated());
            $this->completenessService->calculate($employee);

            return ApiResponse::success($education->fresh(), 'Education record updated successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Education record update');
        }
    }

    public function destroy(Employee $employee, EmployeeEducation $education)
    {
        try {
            if ($education->employee_id !== $employee->id) {
                return ApiResponse::notFound('Education record not found for this employee');
            }

            $education->delete();
            $this->completenessService->calculate($employee);

            return ApiResponse::success(null, 'Education record deleted successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Education record deletion');
        }
    }
}
