<?php

namespace App\Http\Middleware;

use App\Models\Preference\ProfileApprovalSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckApprovalRequired
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $section
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $section)
    {
        $user = Auth::user();

        // If the user is HR, they can bypass approval ONLY if they are editing someone else
        // If they are editing their own profile (Self-Service), they should follow the approval process

        $targetEmployee = $request->route('employee');
        // Handle case where route model binding might have already resolved the model
        $targetEmployeeId = ($targetEmployee instanceof \App\Models\Hris\Employee) ? $targetEmployee->id : $targetEmployee;

        // If no specific employee is targeted, or if targeted employee is distinct from current user
        $isSelfEdit = $user && $user->employee && $targetEmployeeId == $user->employee->id;

        if ($user && $user->is_hr && !$isSelfEdit) {
            return $next($request);
        }

        // Check if the section requires approval for this tenant
        $tenantId = $user->tenant_id;
        $requiresApproval = ProfileApprovalSetting::where('tenant_id', $tenantId)
            ->where('section', $section)
            ->where('requires_approval', true)
            ->exists();


        if ($requiresApproval) {
            return response()->json([
                'success' => false,
                'message' => 'Direct updates to this section are restricted. Please submit a profile change request for approval.',
                'approval_required' => true,
                'section' => $section
            ], 403);
        }

        return $next($request);
    }
}
