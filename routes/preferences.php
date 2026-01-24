<?php

use App\Http\Controllers\Preference\PreferenceController;
use Illuminate\Support\Facades\Route;

Route::prefix('preferences')->group(function () {
    Route::get('/', [PreferenceController::class, 'index']);
    Route::post('/sync', [PreferenceController::class, 'sync']);
    Route::get('/category/{category}', [PreferenceController::class, 'getByCategory']);
    Route::get('/available-admins', [PreferenceController::class, 'searchAvailableAdmins']);
    Route::delete('/{category}/{key}', [PreferenceController::class, 'destroy']);

    // Approval Settings (HR only)
    Route::get('/approval-settings', [\App\Http\Controllers\Preference\ApprovalSettingsController::class, 'index']);
    Route::put('/approval-settings', [\App\Http\Controllers\Preference\ApprovalSettingsController::class, 'update']);

    // User Security/Privacy History
    Route::get('/my-activity-history', [\App\Http\Controllers\HRIS\EmployeeController::class, 'myAuditLogs']);

    // Security Settings
    Route::prefix('security')->group(function () {
        Route::post('/change-password', [\App\Http\Controllers\Auth\AuthController::class, 'changePassword']);
        Route::get('/sessions', [\App\Http\Controllers\Auth\AuthController::class, 'getSessions']);
        Route::delete('/sessions/{id}', [\App\Http\Controllers\Auth\AuthController::class, 'revokeSession']);
    });
});
