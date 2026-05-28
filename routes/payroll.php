<?php

use Illuminate\Support\Facades\Route;

// Payroll Module routes
Route::prefix('payroll')->group(function () {
    // Analytics
    Route::get('analytics', [App\Http\Controllers\Payroll\PayrollAnalyticsController::class, 'index']);

    // PAYE Schemes & Bands
    Route::prefix('setup')->group(function () {
        // Tax Schemes
        Route::get('tax-schemes', [App\Http\Controllers\Payroll\TaxSchemeController::class, 'index']);
        Route::post('tax-schemes', [App\Http\Controllers\Payroll\TaxSchemeController::class, 'store']);
        Route::get('tax-schemes/{id}', [App\Http\Controllers\Payroll\TaxSchemeController::class, 'show']);
        Route::put('tax-schemes/{id}', [App\Http\Controllers\Payroll\TaxSchemeController::class, 'update']);
        Route::delete('tax-schemes/{id}', [App\Http\Controllers\Payroll\TaxSchemeController::class, 'destroy']);

        // Tax Bands (nested under scheme or separate)
        Route::get('tax-schemes/{scheme_id}/bands', [App\Http\Controllers\Payroll\TaxBandController::class, 'index']);
        Route::post('tax-schemes/{scheme_id}/bands', [App\Http\Controllers\Payroll\TaxBandController::class, 'store']);
    });

    // Wage Items
    Route::prefix('wage-items')->group(function () {
        Route::get('/', [App\Http\Controllers\Payroll\WageItemController::class, 'index']);
        Route::post('/', [App\Http\Controllers\Payroll\WageItemController::class, 'store']);
        Route::put('{id}', [App\Http\Controllers\Payroll\WageItemController::class, 'update']);
        Route::delete('{id}', [App\Http\Controllers\Payroll\WageItemController::class, 'destroy']);
    });

    // Pay Groups
    Route::post('pay-groups/{id}/assign', [App\Http\Controllers\Payroll\PayGroupController::class, 'assignEmployees']);
    Route::delete('pay-groups/{id}/employees/{employeeId}', [App\Http\Controllers\Payroll\PayGroupController::class, 'unassignEmployee']);
    Route::apiResource('pay-groups', App\Http\Controllers\Payroll\PayGroupController::class);

    // Salary Components
    Route::apiResource('components', App\Http\Controllers\Payroll\SalaryComponentController::class);

    // Annual Salary Structure
    Route::prefix('annual-structures')->group(function () {
        Route::get('/', [App\Http\Controllers\Payroll\AnnualStructureController::class, 'index']);
        Route::get('my-active', [App\Http\Controllers\Payroll\AnnualStructureController::class, 'myActive']);
        Route::post('calculate', [App\Http\Controllers\Payroll\AnnualStructureController::class, 'preview']);
        Route::post('generate', [App\Http\Controllers\Payroll\AnnualStructureController::class, 'generate']);
        Route::post('bulk-delete', [App\Http\Controllers\Payroll\AnnualStructureController::class, 'bulkDestroy']);
        Route::post('bulk-activate', [App\Http\Controllers\Payroll\AnnualStructureController::class, 'bulkActivate']);
        Route::get('{employee_id}', [App\Http\Controllers\Payroll\AnnualStructureController::class, 'show']);
        Route::put('{id}', [App\Http\Controllers\Payroll\AnnualStructureController::class, 'update']);
        Route::patch('{id}/toggle-status', [App\Http\Controllers\Payroll\AnnualStructureController::class, 'toggleStatus']);
        Route::delete('{id}', [App\Http\Controllers\Payroll\AnnualStructureController::class, 'destroy']);
    });

    // Monthly Batch Payments
    Route::prefix('batches')->group(function () {
        Route::get('/', [App\Http\Controllers\Payroll\BatchPaymentController::class, 'index']);
        Route::post('generate', [App\Http\Controllers\Payroll\BatchPaymentController::class, 'generate']);
        Route::get('{id}', [App\Http\Controllers\Payroll\BatchPaymentController::class, 'show']);
        Route::patch('{id}/authorize', [App\Http\Controllers\Payroll\BatchPaymentController::class, 'authorizeBatch']);
        Route::post('bulk-delete', [App\Http\Controllers\Payroll\BatchPaymentController::class, 'bulkDestroy']);
        Route::delete('{id}', [App\Http\Controllers\Payroll\BatchPaymentController::class, 'destroy']);

        // Items & Adjustments
        Route::post('items', [App\Http\Controllers\Payroll\BatchPaymentController::class, 'addItem']);
        Route::post('items/bulk', [App\Http\Controllers\Payroll\BatchPaymentController::class, 'bulkAddItem']);
        Route::put('items/{item_id}', [App\Http\Controllers\Payroll\BatchPaymentController::class, 'updateItem']);
        Route::delete('items/{item_id}', [App\Http\Controllers\Payroll\BatchPaymentController::class, 'removeItem']);
    });

    // Payslips (Employee)
    Route::prefix('payslips')->group(function () {
        Route::get('my-payslips', [App\Http\Controllers\Payroll\PayslipController::class, 'myPayslips']);
        Route::get('{id}', [App\Http\Controllers\Payroll\PayslipController::class, 'show']);
        Route::get('{id}/download', [App\Http\Controllers\Payroll\PayslipController::class, 'download']);
    });

    // Leave Allowance Management
    Route::prefix('leave-allowances')->group(function () {
        Route::get('/', [App\Http\Controllers\Payroll\LeaveAllowanceController::class, 'index']);
        Route::get('summary', [App\Http\Controllers\Payroll\LeaveAllowanceController::class, 'summary']);
        Route::get('{id}', [App\Http\Controllers\Payroll\LeaveAllowanceController::class, 'show']);
        Route::post('{id}/approve', [App\Http\Controllers\Payroll\LeaveAllowanceController::class, 'approve']);
        Route::post('{id}/decline', [App\Http\Controllers\Payroll\LeaveAllowanceController::class, 'decline']);
    });

    // Payroll Reports
    Route::prefix('reports')->group(function () {
        Route::get('monthly-summary', [App\Http\Controllers\Payroll\PayrollReportController::class, 'monthlySummary']);
        Route::get('departmental', [App\Http\Controllers\Payroll\PayrollReportController::class, 'departmentalExpenditure']);
        Route::get('variance', [App\Http\Controllers\Payroll\PayrollReportController::class, 'varianceReport']);
        Route::get('statutory', [App\Http\Controllers\Payroll\PayrollReportController::class, 'statutoryCompliance']);
        Route::get('leave-allowance', [App\Http\Controllers\Payroll\PayrollReportController::class, 'leaveAllowanceReconciliation']);
        Route::get('annual-audit', [App\Http\Controllers\Payroll\PayrollReportController::class, 'annualSalaryAudit']);
    });
});
