<?php

namespace App\Http\Controllers\Requests;

use App\Http\Controllers\Controller;
use App\Models\Requests\RequestSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RequestReportController extends Controller
{
    public function analytics(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        $year = $request->get('year', Carbon::now()->year);

        $baseQuery = RequestSubmission::where('request_submissions.tenant_id', $tenantId)
            ->whereYear('request_submissions.submitted_at', $year);

        if ($request->filled('department_id')) {
            $baseQuery->whereHas('employee', function ($q) use ($request) {
                $q->whereHas('employmentDetails', function ($q2) use ($request) {
                    $q2->where('department_id', $request->department_id);
                });
            });
        }

        // 1. Total Volume
        $totalVolume = [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->whereIn('request_submissions.status', ['pending', 'in_progress'])->count(),
            'approved' => (clone $baseQuery)->where('request_submissions.status', 'approved')->count(),
            'declined' => (clone $baseQuery)->where('request_submissions.status', 'declined')->count(),
            'cancelled' => (clone $baseQuery)->where('request_submissions.status', 'cancelled')->count(),
        ];

        // 2. Template Distribution (Grouped accurately so we don't clash field names)
        $templateDistribution = (clone $baseQuery)
            ->join('request_templates', 'request_submissions.template_id', '=', 'request_templates.id')
            ->select('request_templates.name as template_name', DB::raw('count(*) as count'))
            ->groupBy('request_templates.id', 'request_templates.name')
            ->get();

        // 3. Departmental Volume
        $departmentalVolume = (clone $baseQuery)
            ->join('employees', 'request_submissions.employee_id', '=', 'employees.id')
            ->join('employee_employment_details', 'employees.id', '=', 'employee_employment_details.employee_id')
            ->join('departments', 'employee_employment_details.department_id', '=', 'departments.id')
            ->select('departments.name as department', DB::raw('count(*) as count'))
            ->groupBy('departments.id', 'departments.name')
            ->get();

        // 4. Monthly Trend
        $monthlyTrendRaw = (clone $baseQuery)
            ->select(DB::raw('MONTH(request_submissions.submitted_at) as month'), DB::raw('count(*) as count'))
            ->groupBy('month')
            ->pluck('count', 'month')
            ->toArray();

        $monthlyTrend = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthlyTrend[] = [
                'month' => Carbon::create()->month($i)->format('M'),
                'count' => $monthlyTrendRaw[$i] ?? 0,
            ];
        }

        // 5. Processing Latency (Approved only, tracking from submission to completion)
        // Using average date diff in days. DATEDIFF is standard SQL (works in MySQL).
        $latencyRaw = (clone $baseQuery)
            ->where('request_submissions.status', 'approved')
            ->whereNotNull('request_submissions.completed_at')
            ->select(DB::raw('AVG(DATEDIFF(request_submissions.completed_at, request_submissions.submitted_at)) as avg_days'))
            ->first();
            
        $averageLatencyDays = $latencyRaw ? round($latencyRaw->avg_days ?? 0, 2) : 0;

        return response()->json([
            'status' => 'success',
            'data' => [
                'volume' => $totalVolume,
                'template_distribution' => $templateDistribution,
                'departmental_volume' => $departmentalVolume,
                'monthly_trend' => $monthlyTrend,
                'latency_days' => $averageLatencyDays,
            ]
        ]);
    }

    public function history(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        $query = RequestSubmission::with(['template', 'employee.employmentDetails.department'])
            ->where('tenant_id', $tenantId);

        if ($request->has('template_id')) {
            $query->where('template_id', $request->template_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('submitted_at', [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay()
            ]);
        }

        if ($request->has('department_id')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->whereHas('employmentDetails', function ($q2) use ($request) {
                    $q2->where('department_id', $request->department_id);
                });
            });
        }

        $submissions = $query->orderBy('submitted_at', 'desc')->get()->map(function ($sub) {
            $latency = null;
            if ($sub->completed_at) {
                $latency = round($sub->submitted_at->floatDiffInDays($sub->completed_at), 2);
            }
            return [
                'id' => $sub->id,
                'reference_number' => $sub->reference_number,
                'employee_number' => $sub->employee->employee_number ?? 'N/A',
                'employee_name' => $sub->employee->full_name ?? 'N/A',
                'department' => $sub->employee->employmentDetails->department->name ?? 'N/A',
                'template_name' => $sub->template->name ?? 'N/A',
                'status' => ucfirst($sub->status),
                'submitted_at' => $sub->submitted_at->format('Y-m-d'),
                'completed_at' => $sub->completed_at ? $sub->completed_at->format('Y-m-d') : null,
                'latency_days' => $latency ?? '-',
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $submissions
        ]);
    }
}
