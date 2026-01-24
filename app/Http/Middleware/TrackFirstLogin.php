<?php

namespace App\Http\Middleware;

use App\Models\Hris\EmployeeOnboardingStatus;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrackFirstLogin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // Only track if user is authenticated and has an employee record
        if ($user && $user->employee) {
            $onboardingStatus = EmployeeOnboardingStatus::where('employee_id', $user->employee->id)->first();

            // If onboarding status exists and first login hasn't been completed yet
            if ($onboardingStatus && !$onboardingStatus->first_login_completed) {
                $onboardingStatus->update([
                    'first_login_completed' => true,
                    'first_login_at' => now(),
                ]);
            }
        }

        return $next($request);
    }
}
