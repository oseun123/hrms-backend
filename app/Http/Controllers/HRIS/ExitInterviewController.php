<?php

namespace App\Http\Controllers\HRIS;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Hris\Employee;
use App\Models\Hris\EmployeeExitInterview;
use App\Traits\HandlesApiErrors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ExitInterviewController extends Controller
{
    use HandlesApiErrors;

    /**
     * Show exit interview for an employee
     */
    public function show(Employee $employee)
    {
        try {
            $exitInterview = $employee->exitInterview()->with('interviewer:id,name,email')->first();

            return ApiResponse::success($exitInterview);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Fetching exit interview');
        }
    }

    /**
     * Store or update exit interview for an employee
     */
    public function storeOrUpdate(Request $request, Employee $employee)
    {
        try {
            // Check that the employee is terminated
            $isTerminated = !$employee->is_active 
                || $employee->employmentDetails?->employment_status === 'terminated'
                || !empty($employee->employmentDetails?->termination_date);

            if (!$isTerminated) {
                return ApiResponse::error('An Exit Interview can only be conducted for employees who have been terminated.', 422);
            }

            $validator = Validator::make($request->all(), [
                'interview_date' => 'required|date',
                'interviewer_id' => 'nullable|exists:users,id',
                'primary_reason_for_leaving' => 'nullable|string',
                'secondary_reasons' => 'nullable|array',
                'overall_experience_rating' => 'nullable|integer|min:1|max:5',
                'management_rating' => 'nullable|integer|min:1|max:5',
                'compensation_rating' => 'nullable|integer|min:1|max:5',
                'work_life_balance_rating' => 'nullable|integer|min:1|max:5',
                'growth_opportunities_rating' => 'nullable|integer|min:1|max:5',
                'culture_rating' => 'nullable|integer|min:1|max:5',
                'what_went_well' => 'nullable|string',
                'what_could_improve' => 'nullable|string',
                'additional_comments' => 'nullable|string',
                'handover_completed' => 'nullable|boolean',
                'assets_returned' => 'nullable|boolean',
                'rehire_eligibility' => 'nullable|string|in:eligible,conditional,ineligible',
                'rehire_notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return ApiResponse::validationError($validator->errors());
            }

            $validated = $validator->validated();

            $exitInterview = EmployeeExitInterview::updateOrCreate(
                [
                    'tenant_id' => $employee->tenant_id,
                    'employee_id' => $employee->id,
                ],
                $validated
            );

            return ApiResponse::success(
                $exitInterview->load('interviewer:id,name,email'),
                'Exit interview saved successfully'
            );
        } catch (\Exception $e) {
            return $this->handleException($e, 'Saving exit interview');
        }
    }
}
