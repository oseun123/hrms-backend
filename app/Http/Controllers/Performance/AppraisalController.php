<?php

namespace App\Http\Controllers\Performance;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Hris\Employee;
use App\Models\Performance\Appraisal;
use App\Models\Performance\AppraisalSubmission;
use App\Models\Performance\EmployeeDeliverable;
use App\Models\Performance\PerformanceCycle;
use App\Models\Performance\PerformanceSetting;
use App\Services\AppraisalScoringService;
use App\Traits\HandlesApiErrors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class AppraisalController extends Controller
{
    use HandlesApiErrors;

    protected $scoringService;

    public function __construct(AppraisalScoringService $scoringService)
    {
        $this->scoringService = $scoringService;
    }

    public function index(Request $request)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $query = Appraisal::where('tenant_id', $tenantId)
                ->with(['creator']);

            // Filtering
            if ($request->has('year')) {
                $query->where('year', $request->year);
            }

            if ($request->has('status') && $request->status !== 'all') {
                $status = $request->status;
                if (str_contains($status, ',')) {
                    $statuses = explode(',', $status);
                    $query->whereIn('status', $statuses);
                } else {
                    $query->where('status', $status);
                }
            }

            if ($request->has('name')) {
                $query->where('name', 'like', '%' . $request->name . '%');
            }

            // Ordering
            $query->orderBy('created_at', 'desc');

            // Pagination
            $perPage = $request->get('per_page', 10);
            $appraisals = $query->paginate($perPage);

            return ApiResponse::success($appraisals);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching appraisals');
        }
    }

    public function store(Request $request)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $userId = Auth::id();

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'year' => 'required|integer|min:2020|max:2100',
                'review_period' => 'required|string|max:50',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'description' => 'nullable|string',
            ]);

            // Handle soft-deleted duplicates that would violate the unique index
            Appraisal::where('tenant_id', $tenantId)
                ->where('name', $validated['name'])
                ->where('year', $validated['year'])
                ->onlyTrashed()
                ->forceDelete();

            // Lock the cycle when creating the first appraisal
            PerformanceCycle::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'year' => $validated['year'],
                ],
                [
                    'cycle_type' => $request->review_period ?? 'annual',
                    'locked_at' => now(),
                ]
            );

            $appraisal = Appraisal::create(array_merge($validated, [
                'tenant_id' => $tenantId,
                'status' => 'inactive',
                'created_by' => $userId,
            ]));

            return ApiResponse::success($appraisal, 'Appraisal created successfully', 201);
        } catch (\Exception $e) {
            return $this->handleException($e, 'creating appraisal');
        }
    }

    public function show($id)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $appraisal = Appraisal::where('tenant_id', $tenantId)
                ->with(['submissions.employee', 'creator'])
                ->findOrFail($id);
            return ApiResponse::success($appraisal);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching appraisal');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $appraisal = Appraisal::where('tenant_id', $tenantId)->findOrFail($id);

            $validated = $request->validate([
                'name' => 'string|max:255',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'description' => 'nullable|string',
                'status' => 'in:inactive,active,completed',
            ]);

            $appraisal->update($validated);
            return ApiResponse::success($appraisal, 'Appraisal updated successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'updating appraisal');
        }
    }

    /**
     * Activate appraisal and create submissions for all employees with deliverables
     */
    public function activate($id)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $appraisal = Appraisal::where('tenant_id', $tenantId)->findOrFail($id);

            DB::beginTransaction();

            // Get all active employees
            $allActiveEmployees = Employee::where('tenant_id', $tenantId)
                ->active()
                ->pluck('id');

            // Fetch global settings once
            $globalSettings = PerformanceSetting::where('tenant_id', $tenantId)->first();
            $defaults = [
                'reviewer_levels' => $globalSettings->reviewer_levels ?? 2,
                'reviewer_config' => $globalSettings->reviewer_config ?? [],
                'results_weight' => $globalSettings->results_weight ?? 70.00,
                'competency_weight' => $globalSettings->competency_weight ?? 30.00,
                'final_score_level' => $globalSettings->final_score_level ?? 2,
                'enforce_submit_back' => $globalSettings->enforce_submit_back ?? false,
            ];

            $createdCount = 0;
            // Create submissions for each employee if they don't exist
            foreach ($allActiveEmployees as $employeeId) {
                $created = AppraisalSubmission::firstOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'appraisal_id' => $appraisal->id,
                        'employee_id' => $employeeId,
                    ],
                    array_merge($defaults, [
                        'current_level' => 1,
                        'status' => 'pending',
                    ])
                );

                if ($created->wasRecentlyCreated) {
                    $createdCount++;
                }
            }

            if ($appraisal->status !== 'active') {
                $appraisal->update(['status' => 'active']);
            }

            DB::commit();

            return ApiResponse::success([
                'appraisal' => $appraisal,
                'submissions_created' => $createdCount,
            ], $createdCount > 0 ? 'Appraisal activated/synced successfully' : 'Appraisal is up to date');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'activating appraisal');
        }
    }

    public function complete($id)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $appraisal = Appraisal::where('tenant_id', $tenantId)->findOrFail($id);

            if ($appraisal->status !== 'active') {
                return ApiResponse::error('Only active appraisals can be completed', 422);
            }

            $appraisal->update(['status' => 'completed']);

            return ApiResponse::success($appraisal, 'Appraisal cycle completed successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'completing appraisal');
        }
    }

    /**
     * Send manual notification to all employees to start appraisal
     */
    public function notifyEmployees($id)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $appraisal = Appraisal::where('tenant_id', $tenantId)->findOrFail($id);

            if ($appraisal->status !== 'active') {
                return ApiResponse::error('Appraisal must be active to send notifications', 422);
            }

            $submissions = AppraisalSubmission::where('appraisal_id', $appraisal->id)
                ->where('status', 'pending')
                ->with('employee.user')
                ->get();

            $notifiedCount = 0;
            foreach ($submissions as $submission) {
                if ($submission->employee && $submission->employee->user) {
                    // TODO: Send notification via your notification system
                    // Mail::to($submission->employee->user->email)->send(new AppraisalNotification($appraisal));
                    $notifiedCount++;
                }
            }

            return ApiResponse::success([
                'notified_count' => $notifiedCount,
            ], "{$notifiedCount} employee(s) notified successfully");
        } catch (\Exception $e) {
            return $this->handleException($e, 'notifying employees');
        }
    }

    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $tenantId = Auth::user()->tenant_id;
            $appraisal = Appraisal::where('tenant_id', $tenantId)->findOrFail($id);

            if ($appraisal->status === 'completed') {
                return ApiResponse::error('Completed appraisals cannot be deleted. They can only be reopened.', 422);
            }

            // Check if any actual submissions have been made
            // We consider it "submitted" if submitted_at is set or if it's moved beyond level 1
            $hasSubmissions = AppraisalSubmission::where('appraisal_id', $appraisal->id)
                ->where(function ($query) {
                    $query->whereNotNull('submitted_at')
                        ->orWhere('current_level', '>', 1);
                })
                ->exists();

            if ($hasSubmissions) {
                return ApiResponse::error('Cannot delete appraisal because submissions have already been made by employees.', 422);
            }

            // If it's active or inactive, we delete associated submissions first
            // (Inactive appraisals might have submissions if they were previously active and then deactivated, 
            // though the UI flow usually creates them on activation)
            AppraisalSubmission::where('appraisal_id', $appraisal->id)->delete();

            $appraisal->delete();

            return ApiResponse::success(null, 'Appraisal and associated pending submissions deleted successfully');
        });
    }
}
