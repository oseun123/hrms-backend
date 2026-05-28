<?php

use App\Http\Controllers\Performance\AppraisalController;
use App\Http\Controllers\Performance\AppraisalAnalyticsController;
use App\Http\Controllers\Performance\AppraisalReportController;
use App\Http\Controllers\Performance\AppraisalSubmissionController;
use App\Http\Controllers\Performance\AppraisalTrackingController;
use App\Http\Controllers\Performance\AreaOfFocusController;
use App\Http\Controllers\Performance\CompetencyController;
use App\Http\Controllers\Performance\DeliverableController;
use App\Http\Controllers\Performance\GoalController;
use App\Http\Controllers\Performance\PerformanceSettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Performance Management Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum'])->prefix('performance')->group(function () {

    // Performance Settings & Configuration
    Route::middleware(['permission:performance.setup'])->prefix('settings')->group(function () {
        Route::get('/', [PerformanceSettingsController::class, 'index']);
        Route::put('/', [PerformanceSettingsController::class, 'update']);
        Route::get('/cycle-info', [PerformanceSettingsController::class, 'getCycleInfo']);
    });

    // Areas of Focus
    Route::middleware(['permission:performance.setup'])->prefix('areas-of-focus')->group(function () {
        Route::get('/', [AreaOfFocusController::class, 'index']);
        Route::post('/', [AreaOfFocusController::class, 'store']);
        Route::get('/{id}', [AreaOfFocusController::class, 'show']);
        Route::put('/{id}', [AreaOfFocusController::class, 'update']);
        Route::delete('/{id}', [AreaOfFocusController::class, 'destroy']);
    });

    // Goals Management
    Route::middleware(['permission:performance.setup'])->prefix('goals')->group(function () {
        Route::get('/', [GoalController::class, 'index']);
        Route::get('/template/simple', [GoalController::class, 'downloadSimpleTemplate']);
        Route::get('/template/complex', [GoalController::class, 'downloadComplexTemplate']);
        Route::post('/import', [GoalController::class, 'import']);
        Route::post('/', [GoalController::class, 'store']);
        Route::post('/{id}/duplicate', [GoalController::class, 'duplicate']);
        Route::get('/{id}', [GoalController::class, 'show']);
        Route::put('/{id}', [GoalController::class, 'update']);
        Route::delete('/{id}', [GoalController::class, 'destroy']);
        Route::post('/bulk-delete', [GoalController::class, 'bulkDestroy']);
    });

    // Competencies
    Route::middleware(['permission:performance.setup'])->prefix('competencies')->group(function () {
        Route::get('/', [CompetencyController::class, 'index']);
        Route::post('/', [CompetencyController::class, 'store']);
        Route::put('/bulk-weightages', [CompetencyController::class, 'updateBulkWeightages']);
        Route::get('/{id}', [CompetencyController::class, 'show']);
        Route::put('/{id}', [CompetencyController::class, 'update']);
        Route::delete('/{id}', [CompetencyController::class, 'destroy']);
    });

    // Deliverables
    Route::prefix('deliverables')->group(function () {
        Route::get('/', [DeliverableController::class, 'index'])->middleware('permission:performance.setup');
        Route::get('/my', [DeliverableController::class, 'getMyDeliverables'])->middleware('permission:performance.my_deliverables');
        Route::get('/team', [DeliverableController::class, 'getTeamDeliverables'])->middleware('permission:performance.team_deliverables');
        Route::get('/employees', [DeliverableController::class, 'index'])->middleware('permission:performance.employee_deliverables');

        Route::middleware(['permission:performance.setup'])->group(function () {
            Route::post('/assign', [DeliverableController::class, 'assign']);
            Route::post('/activate', [DeliverableController::class, 'activate']);
            Route::post('/deactivate', [DeliverableController::class, 'deactivate']);
            Route::post('/check-activation', [DeliverableController::class, 'checkActivation']);
            Route::delete('/{id}', [DeliverableController::class, 'destroy']);
            Route::post('/bulk-delete', [DeliverableController::class, 'bulkDestroy']);
        });
    });

    // Appraisals
    Route::middleware(['permission:performance.appraisal_management'])->prefix('appraisals')->group(function () {
        Route::get('/', [AppraisalController::class, 'index']);
        Route::post('/', [AppraisalController::class, 'store']);
        Route::get('/{id}', [AppraisalController::class, 'show']);
        Route::put('/{id}', [AppraisalController::class, 'update']);
        Route::delete('/{id}', [AppraisalController::class, 'destroy']);
        Route::post('/{id}/activate', [AppraisalController::class, 'activate']);
        Route::post('/{id}/notify', [AppraisalController::class, 'notifyEmployees']);
        Route::post('/{id}/complete', [AppraisalController::class, 'complete']);
    });

    // Appraisal Submissions
    Route::prefix('submissions')->group(function () {
        Route::get('/my-pending', [AppraisalSubmissionController::class, 'getMyPending'])->middleware('permission:performance.my_deliverables');
        Route::get('/my-history', [AppraisalSubmissionController::class, 'getMyHistory'])->middleware('permission:performance.my_deliverables');

        Route::middleware(['permission:performance.my_deliverables'])->group(function () {
            Route::get('/{id}', [AppraisalSubmissionController::class, 'show']);
            Route::post('/{id}/submit-scores', [AppraisalSubmissionController::class, 'submitScores']);
            Route::post('/{id}/forward', [AppraisalSubmissionController::class, 'forward']);
            Route::post('/{id}/upload-attachment', [AppraisalSubmissionController::class, 'uploadAttachment']);
            Route::delete('/attachments/{id}', [AppraisalSubmissionController::class, 'deleteAttachment']);
        });

        Route::middleware(['permission:performance.appraisal_management'])->group(function () {
            Route::post('/{id}/return', [AppraisalSubmissionController::class, 'returnToEmployee']);
            Route::post('/{id}/accept-return', [AppraisalSubmissionController::class, 'acceptReturn']);
            Route::post('/{id}/reject-return', [AppraisalSubmissionController::class, 'rejectReturn']);
            Route::post('/{id}/restart', [AppraisalSubmissionController::class, 'restart']);
            Route::post('/bulk-restart', [AppraisalSubmissionController::class, 'bulkRestart']);
            Route::put('/{id}/settings', [AppraisalSubmissionController::class, 'updateSettings']);
            Route::post('/{id}/refresh-configuration', [AppraisalSubmissionController::class, 'refreshConfiguration']);
        });
    });

    // Appraisal Tracking
    Route::middleware(['permission:performance.dashboard'])->prefix('tracking')->group(function () {
        Route::get('/appraisal/{appraisalId}', [AppraisalTrackingController::class, 'getAppraisalSubmissions']);
        Route::get('/appraisal/{appraisalId}/stats', [AppraisalTrackingController::class, 'getAppraisalStats']);
        Route::get('/appraisal/{appraisalId}/employee/{employeeId}', [AppraisalTrackingController::class, 'getEmployeeTracking']);
    });

    // Appraisal Analytics
    Route::middleware(['permission:performance.dashboard'])->prefix('analytics')->group(function () {
        Route::get('/appraisal/{appraisalId}/completion-stats', [AppraisalAnalyticsController::class, 'getCycleCompletionStats']);
        Route::get('/appraisal/{appraisalId}/score-distribution', [AppraisalAnalyticsController::class, 'getScoreDistribution']);
        Route::get('/appraisal/{appraisalId}/department-averages', [AppraisalAnalyticsController::class, 'getDepartmentAverages']);
        Route::get('/appraisal/{appraisalId}/top-bottom-performers', [AppraisalAnalyticsController::class, 'getTopBottomPerformers']);
        Route::get('/appraisal/{appraisalId}/goals-vs-competencies', [AppraisalAnalyticsController::class, 'getGoalsVsCompetencies']);
    });

    // Appraisal Reports
    Route::middleware(['permission:performance.reports'])->prefix('reports')->group(function () {
        Route::get('/appraisal/{appraisalId}/cycle-status', [AppraisalReportController::class, 'cycleStatusReport']);
        Route::get('/appraisal/{appraisalId}/league-table', [AppraisalReportController::class, 'performanceLeagueTable']);
        Route::get('/appraisal/{appraisalId}/departmental', [AppraisalReportController::class, 'departmentalPerformance']);
        Route::get('/appraisal/{appraisalId}/competency-gap', [AppraisalReportController::class, 'competencyGapAnalysis']);
        Route::get('/appraisal/{appraisalId}/pending-reviews', [AppraisalReportController::class, 'pendingReviews']);
    });
});
