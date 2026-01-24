<?php

namespace App\Http\Controllers\HRIS;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\HRIS\StoreDependentRequest;
use App\Http\Requests\HRIS\UpdateDependentRequest;
use App\Models\Hris\Employee;
use App\Models\Hris\EmployeeDependent;
use App\Services\ProfileCompletenessService;
use App\Traits\HandlesApiErrors;

class DependentController extends Controller
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
            return ApiResponse::success($employee->dependents);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Fetching dependents');
        }
    }

    public function store(StoreDependentRequest $request, Employee $employee)
    {
        try {
            $dependent = EmployeeDependent::create(array_merge(
                $request->validated(),
                [
                    'tenant_id' => $employee->tenant_id,
                    'employee_id' => $employee->id,
                ]
            ));

            $this->completenessService->calculate($employee);

            return ApiResponse::created($dependent, 'Dependent created successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Dependent creation');
        }
    }

    public function update(UpdateDependentRequest $request, Employee $employee, EmployeeDependent $dependent)
    {
        try {
            if ($dependent->employee_id !== $employee->id) {
                return ApiResponse::notFound('Dependent not found for this employee');
            }

            $dependent->update($request->validated());
            $this->completenessService->calculate($employee);

            return ApiResponse::success($dependent->fresh(), 'Dependent updated successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Dependent update');
        }
    }

    public function destroy(Employee $employee, EmployeeDependent $dependent)
    {
        try {
            if ($dependent->employee_id !== $employee->id) {
                return ApiResponse::notFound('Dependent not found for this employee');
            }

            $dependent->delete();
            $this->completenessService->calculate($employee);

            return ApiResponse::success(null, 'Dependent deleted successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Dependent deletion');
        }
    }
}
