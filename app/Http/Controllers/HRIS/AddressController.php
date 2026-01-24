<?php

namespace App\Http\Controllers\HRIS;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\HRIS\StoreAddressRequest;
use App\Http\Requests\HRIS\UpdateAddressRequest;
use App\Models\Hris\Employee;
use App\Models\Hris\EmployeeAddress;
use App\Services\ProfileCompletenessService;
use App\Traits\HandlesApiErrors;

class AddressController extends Controller
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
            return ApiResponse::success($employee->addresses);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Fetching addresses');
        }
    }

    public function store(StoreAddressRequest $request, Employee $employee)
    {
        try {
            // If setting as primary, unset other primary addresses
            if ($request->is_primary) {
                $employee->addresses()->update(['is_primary' => false]);
            }

            $address = EmployeeAddress::create(array_merge(
                $request->validated(),
                [
                    'tenant_id' => $employee->tenant_id,
                    'employee_id' => $employee->id,
                ]
            ));

            $this->completenessService->calculate($employee);

            return ApiResponse::created($address, 'Address created successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Address creation');
        }
    }

    public function update(UpdateAddressRequest $request, Employee $employee, EmployeeAddress $address)
    {
        try {
            // Verify address belongs to employee
            if ($address->employee_id !== $employee->id) {
                return ApiResponse::notFound('Address not found for this employee');
            }

            // If setting as primary, unset other primary addresses
            if ($request->is_primary) {
                $employee->addresses()->where('id', '!=', $address->id)->update(['is_primary' => false]);
            }

            $address->update($request->validated());
            $this->completenessService->calculate($employee);

            return ApiResponse::success($address->fresh(), 'Address updated successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Address update');
        }
    }

    public function destroy(Employee $employee, EmployeeAddress $address)
    {
        try {
            // Verify address belongs to employee
            if ($address->employee_id !== $employee->id) {
                return ApiResponse::notFound('Address not found for this employee');
            }

            $address->delete();
            $this->completenessService->calculate($employee);

            return ApiResponse::success(null, 'Address deleted successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Address deletion');
        }
    }
}
