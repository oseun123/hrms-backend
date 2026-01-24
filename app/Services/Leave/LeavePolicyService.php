<?php

namespace App\Services\Leave;

use App\Models\Leave\LeavePolicy;
use App\Models\Hris\Employee;
use Carbon\Carbon;

class LeavePolicyService
{
    /**
     * Validate a leave request against the applicable policy.
     * 
     * @param Employee $employee
     * @param LeavePolicy $policy
     * @param string $startDate
     * @param string $endDate
     * @param float $duration
     * @return array ['is_valid' => bool, 'message' => string]
     */
    public function validatePolicy(Employee $employee, LeavePolicy $policy, $startDate, $endDate, $duration)
    {
        $start = Carbon::parse($startDate);
        $today = Carbon::today();

        // 1. Notice Period Check
        if ($policy->notice_period_days > 0) {
            $minNoticeDate = $today->copy()->addDays($policy->notice_period_days);
            if ($start->lt($minNoticeDate) && $start->gte($today)) {
                return [
                    'is_valid' => false,
                    'message' => "This leave requires a {$policy->notice_period_days}-day notice period. Earliest allowed start date is {$minNoticeDate->toDateString()}."
                ];
            }
        }

        // 2. Minimum Service Check (Probation)
        if ($policy->min_service_days > 0 && $employee->employmentDetails->hire_date) {
            $serviceDays = $employee->employmentDetails->hire_date->diffInDays($today);
            if ($serviceDays < $policy->min_service_days) {
                $eligibleDate = $employee->employmentDetails->hire_date->copy()->addDays($policy->min_service_days);
                return [
                    'is_valid' => false,
                    'message' => "You are not yet eligible for this leave type. Eligibility starts on {$eligibleDate->toDateString()} (after {$policy->min_service_days} days of service)."
                ];
            }
        }

        // 3. Maximum Consecutive Days Check
        if ($policy->max_consecutive_days > 0 && $duration > $policy->max_consecutive_days) {
            return [
                'is_valid' => false,
                'message' => "This leave type allows a maximum of {$policy->max_consecutive_days} consecutive days."
            ];
        }

        // 4. Backdated Leave Check
        if ($start->lt($today)) {
            if (!$policy->allow_backdated_leave) {
                return [
                    'is_valid' => false,
                    'message' => "Backdated applications are not allowed for this leave type."
                ];
            }

            $backdatedDays = $start->diffInDays($today);
            if ($policy->max_backdated_days > 0 && $backdatedDays > $policy->max_backdated_days) {
                return [
                    'is_valid' => false,
                    'message' => "Backdated applications for this leave type are limited to {$policy->max_backdated_days} days in the past."
                ];
            }
        }

        return ['is_valid' => true, 'message' => 'Success'];
    }
}
