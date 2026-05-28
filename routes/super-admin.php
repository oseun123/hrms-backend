<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Super Admin API Routes
|--------------------------------------------------------------------------
|
| These routes are exclusively for the super admin portal. They use a
| separate 'super-admin' Sanctum guard and are completely isolated
| from the regular tenant user routes.
|
*/

// Public super admin auth routes
Route::prefix('super-admin/auth')->group(function () {
    Route::post('/login', [\App\Http\Controllers\SuperAdmin\Auth\SuperAdminAuthController::class, 'login']);
});

// Protected super admin routes
Route::prefix('super-admin')
    ->middleware(['auth.super-admin'])
    ->group(function () {

        // Auth
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [\App\Http\Controllers\SuperAdmin\Auth\SuperAdminAuthController::class, 'logout']);
            Route::get('/me', [\App\Http\Controllers\SuperAdmin\Auth\SuperAdminAuthController::class, 'me']);
        });

        // Tenant Management
        Route::prefix('tenants')->group(function () {
            Route::get('/', [\App\Http\Controllers\SuperAdmin\TenantManagementController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\SuperAdmin\TenantManagementController::class, 'store']);
            Route::get('/{id}', [\App\Http\Controllers\SuperAdmin\TenantManagementController::class, 'show']);
            Route::put('/{id}', [\App\Http\Controllers\SuperAdmin\TenantManagementController::class, 'update']);
            Route::patch('/{id}/activate', [\App\Http\Controllers\SuperAdmin\TenantManagementController::class, 'activate']);
            Route::patch('/{id}/deactivate', [\App\Http\Controllers\SuperAdmin\TenantManagementController::class, 'deactivate']);
            Route::post('/{id}/welcome-email', [\App\Http\Controllers\SuperAdmin\TenantManagementController::class, 'sendWelcomeEmail']);
            Route::delete('/{id}', [\App\Http\Controllers\SuperAdmin\TenantManagementController::class, 'destroy']);
        });

        // Activity Logs
        Route::get('/activity-logs', [\App\Http\Controllers\SuperAdmin\TenantManagementController::class, 'activityLogs']);
    });
