<?php

namespace App\Http\Controllers\HRIS;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\HRIS\StoreFinancialDetailsRequest;
use App\Http\Requests\HRIS\UpdateFinancialDetailsRequest;
use App\Models\Hris\Employee;
use App\Models\Hris\EmployeeFinancialDetail;
use App\Services\ProfileCompletenessService;
use App\Traits\HandlesApiErrors;

class FinancialDetailsController extends Controller
{
    use HandlesApiErrors;

    protected $completenessService;

    public function __construct(ProfileCompletenessService $completenessService)
    {
        $this->completenessService = $completenessService;
    }

    /**
     * Store financial details for an employee
     */
    public function store(StoreFinancialDetailsRequest $request, Employee $employee)
    {
        try {
            $financialDetails = EmployeeFinancialDetail::create(array_merge(
                $request->validated(),
                [
                    'tenant_id' => $employee->tenant_id,
                    'employee_id' => $employee->id,
                ]
            ));

            // Recalculate profile completeness
            $this->completenessService->calculate($employee);

            return ApiResponse::created($financialDetails, 'Financial details created successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Financial details creation');
        }
    }

    /**
     * Update financial details for an employee
     */
    public function update(UpdateFinancialDetailsRequest $request, Employee $employee)
    {
        try {
            $financialDetails = $employee->financialDetails;

            if (! $financialDetails) {
                return ApiResponse::notFound('Financial details not found. Please create them first.');
            }

            $financialDetails->update($request->validated());

            // Recalculate profile completeness
            $this->completenessService->calculate($employee);

            return ApiResponse::success($financialDetails->fresh(), 'Financial details updated successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Financial details update');
        }
    }

    /**
     * Get financial details for an employee
     */
    public function show(Employee $employee)
    {
        try {
            $financialDetails = $employee->financialDetails;

            if (! $financialDetails) {
                return ApiResponse::notFound('Financial details not found');
            }

            return ApiResponse::success($financialDetails);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Fetching financial details');
        }
    }
}
