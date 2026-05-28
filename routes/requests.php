<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Requests\RequestTemplateController;
use App\Http\Controllers\Requests\RequestSubmissionController;
use App\Http\Controllers\Requests\RequestApprovalController;
use App\Http\Controllers\Requests\RequestWorkflowController;
use App\Http\Controllers\Requests\RequestDashboardController;
use App\Http\Controllers\Requests\RequestReportController;

Route::group(['prefix' => 'requests'], function () {
    // Dashboard Stats
    Route::get('stats', [RequestDashboardController::class, 'stats']);

    // Templates
    Route::get('templates', [RequestTemplateController::class, 'index']);
    Route::post('templates', [RequestTemplateController::class, 'store']);
    Route::get('templates/{id}', [RequestTemplateController::class, 'show']);
    Route::put('templates/{id}', [RequestTemplateController::class, 'update']);
    Route::delete('templates/{id}', [RequestTemplateController::class, 'destroy']);

    // Submissions
    Route::get('submissions/my', [RequestSubmissionController::class, 'index']);
    Route::post('submissions', [RequestSubmissionController::class, 'store']);
    Route::get('submissions/{id}', [RequestSubmissionController::class, 'show']);
    Route::post('submissions/{id}/cancel', [RequestSubmissionController::class, 'cancel']);
    Route::get('submissions/{id}/download', [RequestSubmissionController::class, 'download']);

    // Approvals
    Route::get('approvals/pending', [RequestApprovalController::class, 'pending']);
    Route::post('approvals/{id}/action', [RequestApprovalController::class, 'action']);
    Route::get('approvals/history', [RequestApprovalController::class, 'history']);

    // Workflows
    Route::get('workflows', [RequestWorkflowController::class, 'index']);
    Route::post('workflows', [RequestWorkflowController::class, 'store']);
    Route::get('workflows/{id}', [RequestWorkflowController::class, 'show']);
    Route::put('workflows/{id}', [RequestWorkflowController::class, 'update']);
    Route::delete('workflows/{id}', [RequestWorkflowController::class, 'destroy']);

    // Reports
    Route::group(['prefix' => 'reports', 'middleware' => ['permission:requests.reports']], function () {
        Route::get('analytics', [RequestReportController::class, 'analytics']);
        Route::get('history', [RequestReportController::class, 'history']);
    });
});
