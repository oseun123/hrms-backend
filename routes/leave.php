<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Leave\LeaveTypeController;
use App\Http\Controllers\Leave\LeaveGroupController;
use App\Http\Controllers\Leave\LeavePolicyController;
use App\Http\Controllers\Leave\LeaveRequestController;
use App\Http\Controllers\Leave\LeaveApprovalController;
use App\Http\Controllers\Leave\LeaveBalanceController;
use App\Http\Controllers\Leave\LeaveAnalyticsController;
use App\Http\Controllers\Leave\LeaveWorkflowController;
use App\Http\Controllers\Leave\LeaveGroupAssignmentController;

Route::prefix('leave')->group(function () {
    // Configuration
    Route::apiResource('types', LeaveTypeController::class);
    Route::apiResource('groups', LeaveGroupController::class);
    Route::apiResource('policies', LeavePolicyController::class);
    Route::apiResource('workflows', LeaveWorkflowController::class);

    // Leave Requests
    Route::get('requests', [LeaveRequestController::class, 'index']);
    Route::post('requests', [LeaveRequestController::class, 'store']);
    Route::put('requests/{id}', [LeaveRequestController::class, 'update']); // Added/Moved as per instruction
    Route::get('requests/calculate-duration', [LeaveRequestController::class, 'calculateDuration']);
    Route::get('requests/{id}', [LeaveRequestController::class, 'show']);
    Route::delete('requests/{id}', [LeaveRequestController::class, 'destroy']);
    Route::post('requests/{id}/cancel', [LeaveRequestController::class, 'cancel']);
    Route::post('requests/{id}/partial-cancel', [LeaveRequestController::class, 'partialCancel']);

    // Approvals
    Route::get('approvals/pending', [LeaveApprovalController::class, 'pending']);
    Route::post('approvals/{id}/action', [LeaveApprovalController::class, 'action']);
    Route::get('approvals/history', [LeaveApprovalController::class, 'history']);
    Route::post('approvals/{id}/nudge', [LeaveApprovalController::class, 'nudge']);

    // Balances
    Route::get('balances', [LeaveBalanceController::class, 'index']);
    Route::get('balances/my-balance', [LeaveBalanceController::class, 'myBalance']);
    Route::post('balances/adjust', [LeaveBalanceController::class, 'adjust']);
    Route::get('balances/adjustments', [LeaveBalanceController::class, 'adjustments']);

    // Analytics
    Route::get('analytics/dashboard-stats', [LeaveAnalyticsController::class, 'dashboardStats']);
    Route::get('analytics/usage', [LeaveAnalyticsController::class, 'usage']);
    Route::get('analytics/calendar', [LeaveAnalyticsController::class, 'calendar']);
    Route::get('analytics/usage-summary', [LeaveAnalyticsController::class, 'usageSummary']);
    Route::get('analytics/history-report', [LeaveAnalyticsController::class, 'historyReport']);
    Route::get('analytics/balance-report', [LeaveAnalyticsController::class, 'balanceReport']);
    Route::get('analytics/liability-report', [LeaveAnalyticsController::class, 'liabilityReport']);
    Route::get('analytics/absenteeism-pattern', [LeaveAnalyticsController::class, 'absenteeismPattern']);
    Route::get('analytics/latency-report', [LeaveAnalyticsController::class, 'latencyReport']);
    Route::get('analytics/conflict-report', [LeaveAnalyticsController::class, 'conflictReport']);
    Route::get('analytics/active-leaves', [LeaveAnalyticsController::class, 'activeLeaves']);

    // Leave Group Assignments
    Route::get('group-assignments', [LeaveGroupAssignmentController::class, 'index']);
    Route::post('group-assignments/assign', [LeaveGroupAssignmentController::class, 'assign']);
    Route::post('group-assignments/bulk-assign', [LeaveGroupAssignmentController::class, 'bulkAssign']);

    // Year-End Processing
    Route::prefix('year-end')->group(function () {
        Route::get('status', [App\Http\Controllers\Leave\LeaveYearEndController::class, 'status']);
        Route::post('process', [App\Http\Controllers\Leave\LeaveYearEndController::class, 'process']);
    });
});
