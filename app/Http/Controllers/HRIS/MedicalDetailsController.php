<?php

namespace App\Http\Controllers\HRIS;

use App\Http\Controllers\Controller;
use App\Http\Requests\HRIS\StoreMedicalDetailsRequest;
use App\Http\Requests\HRIS\UpdateMedicalDetailsRequest;
use App\Models\Employee;
use App\Models\EmployeeMedicalDetail;
use App\Services\ProfileCompletenessService;
use App\Helpers\ApiResponse;
use App\Traits\HandlesApiErrors;

class MedicalDetailsController extends Controller
{
    use HandlesApiErrors;

    protected $completenessService;

    public function __construct(ProfileCompletenessService $completenessService)
    {
        $this->completenessService = $completenessService;
    }

    public function store(StoreMedicalDetailsRequest $request, Employee $employee)
    {
        try {
            $medicalDetails = EmployeeMedicalDetail::create(array_merge(
                $request->validated(),
                [
                    'tenant_id' => $employee->tenant_id,
                    'employee_id' => $employee->id,
                ]
            ));

            $this->completenessService->calculate($employee);

            return ApiResponse::created($medicalDetails, 'Medical details created successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Medical details creation');
        }
    }

    public function update(UpdateMedicalDetailsRequest $request, Employee $employee)
    {
        try {
            $medicalDetails = $employee->medicalDetails;

            if (!$medicalDetails) {
                return ApiResponse::notFound('Medical details not found. Please create them first.');
            }

            $medicalDetails->update($request->validated());
            $this->completenessService->calculate($employee);

            return ApiResponse::success($medicalDetails->fresh(), 'Medical details updated successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Medical details update');
        }
    }

    public function show(Employee $employee)
    {
        try {
            $medicalDetails = $employee->medicalDetails;

            if (!$medicalDetails) {
                return ApiResponse::notFound('Medical details not found');
            }

            return ApiResponse::success($medicalDetails);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Fetching medical details');
        }
    }
}
