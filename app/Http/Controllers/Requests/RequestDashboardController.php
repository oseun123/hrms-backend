<?php

namespace App\Http\Controllers\Requests;

use App\Http\Controllers\Controller;
use App\Models\Requests\RequestSubmission;
use App\Models\Hris\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequestDashboardController extends Controller
{
    public function stats()
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_submitted' => 0,
                    'pending' => 0,
                    'approved' => 0,
                    'declined' => 0,
                ]
            ]);
        }

        $baseQuery = RequestSubmission::where('tenant_id', $tenantId)
            ->where('employee_id', $employee->id);

        $stats = [
            'total_submitted' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->whereIn('status', ['pending', 'in_progress'])->count(),
            'approved' => (clone $baseQuery)->where('status', 'approved')->count(),
            'declined' => (clone $baseQuery)->where('status', 'declined')->count(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }
}
