<?php

namespace App\Http\Controllers\Leave;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Leave\LeaveYearEndProcessing;
use App\Services\LeaveYearService;
use App\Services\Leave\LeaveBalanceService;
use App\Traits\HandlesApiErrors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveYearEndController extends Controller
{
    use HandlesApiErrors;

    protected LeaveYearService $leaveYearService;
    protected LeaveBalanceService $leaveBalanceService;

    public function __construct(
        LeaveYearService $leaveYearService,
        LeaveBalanceService $leaveBalanceService
    ) {
        $this->leaveYearService = $leaveYearService;
        $this->leaveBalanceService = $leaveBalanceService;
    }

    /**
     * Get current year-end status
     */
    public function status()
    {
        try {
            $tenantId = Auth::user()->tenant_id;

            $currentYear = $this->leaveYearService->getCurrentLeaveYear($tenantId);
            $previousYear = $currentYear - 1;

            // Check if previous year has been processed to current year
            $previousYearProcessed = LeaveYearEndProcessing::where('tenant_id', $tenantId)
                ->where('from_year', $previousYear)
                ->with('processedBy:id,name')
                ->first();

            // If previous year hasn't been processed, that's our target
            if (!$previousYearProcessed) {
                $targetYear = $previousYear;
                $isProcessed = false;
                $processingInfo = null;
            } else {
                // If previous year IS processed, we look at the current year
                $targetYear = $currentYear;
                $processing = LeaveYearEndProcessing::where('tenant_id', $tenantId)
                    ->where('from_year', $currentYear)
                    ->with('processedBy:id,name')
                    ->first();
                $isProcessed = $processing !== null;
                $processingInfo = $processing ? [
                    'processed_at' => $processing->processed_at,
                    'processed_by' => $processing->processedBy->name,
                    'employees_processed' => $processing->employees_processed,
                ] : null;
            }

            $nextYear = $targetYear + 1;
            $boundaries = $this->leaveYearService->getLeaveYearBoundaries($targetYear, $tenantId);

            // Safety: Only allow rolling over the current year if we are in the last 2 months of the year
            $today = \Carbon\Carbon::now();
            $yearEndDate = $boundaries['end'];
            $canProcess = !$isProcessed;

            // If we are targeting the CURRENT year (or later), restrict until near the end
            if ($targetYear >= $currentYear && $today->diffInDays($yearEndDate, false) > 60) {
                $canProcess = false;
            }

            return ApiResponse::success([
                'current_year' => $targetYear,
                'next_year' => $nextYear,
                'year_start_date' => $boundaries['start']->format('Y-m-d'),
                'year_end_date' => $boundaries['end']->format('Y-m-d'),
                'year_label' => $this->leaveYearService->getLeaveYearLabel($targetYear, $tenantId),
                'is_processed' => $isProcessed,
                'can_process' => $canProcess,
                'processing_info' => $processingInfo,
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching year-end status');
        }
    }

    /**
     * Process year-end rollover
     */
    public function process(Request $request)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $statusResponse = $this->status()->getData();
            $status = $statusResponse->data;

            if (!$status->can_process) {
                return ApiResponse::error(
                    'Year-end rollover for ' . $status->current_year . ' cannot be processed at this time.',
                    400
                );
            }

            $targetYear = $status->current_year;

            // Process year-end
            $result = $this->leaveBalanceService->processYearEndForTenant($tenantId, $targetYear);

            // Record processing
            LeaveYearEndProcessing::create([
                'tenant_id' => $tenantId,
                'from_year' => $targetYear,
                'to_year' => $targetYear + 1,
                'processed_at' => now(),
                'processed_by' => Auth::id(),
                'employees_processed' => $result['employees_processed'],
                'summary' => $result,
            ]);

            return ApiResponse::success($result, 'Year-end rollover completed successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'processing year-end rollover');
        }
    }
}
