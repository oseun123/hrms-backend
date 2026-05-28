<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [App\Http\Controllers\Auth\AuthController::class, 'login']);
    Route::post('/login/verify-2fa', [App\Http\Controllers\Auth\AuthController::class, 'verify2fa']);
    Route::post('/register', [App\Http\Controllers\Auth\AuthController::class, 'register']);
    Route::post('/password/reset-request', [App\Http\Controllers\Auth\PasswordResetController::class, 'requestReset']);
    Route::post('/password/reset', [App\Http\Controllers\Auth\PasswordResetController::class, 'reset']);
    Route::post('/password/reset-expired', [App\Http\Controllers\Auth\AuthController::class, 'resetExpiredPassword']);
});

// Public tenant lookup
Route::get('/tenants/{slug}', [App\Http\Controllers\TenantController::class, 'getBySlug']);

// Public demo request (landing page form — no auth required)
Route::post('/demo-request', [App\Http\Controllers\DemoRequestController::class, 'store']);

// Protected routes
Route::middleware(['auth:sanctum', 'track.first.login'])->group(function () {

    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [App\Http\Controllers\Auth\AuthController::class, 'logout']);
        Route::get('/me', [App\Http\Controllers\Auth\AuthController::class, 'me']);
        Route::put('/security-settings', [App\Http\Controllers\Auth\AuthController::class, 'updateSecuritySettings']);
        Route::get('/sessions', [App\Http\Controllers\Auth\AuthController::class, 'getSessions']);
        Route::delete('/sessions/{id}', [App\Http\Controllers\Auth\AuthController::class, 'revokeSession']);
        Route::delete('/sessions', [App\Http\Controllers\Auth\AuthController::class, 'revokeOtherSessions']);
    });

    // Tenant routes
    Route::post('/tenant/logo', [App\Http\Controllers\TenantController::class, 'uploadLogo']);
    Route::delete('/tenant/logo', [App\Http\Controllers\TenantController::class, 'deleteLogo']);



    // HRIS Module routes
    require __DIR__ . '/hris.php';

    // Preferences routes
    require __DIR__ . '/preferences.php';

    // Roles & Permissions routes
    require __DIR__ . '/roles.php';

    // Leave Module routes
    require __DIR__ . '/leave.php';

    // Payroll Module routes
    require __DIR__ . '/payroll.php';

    // Performance Module routes
    require __DIR__ . '/performance.php';

    // Request Module routes
    require __DIR__ . '/requests.php';

    /*
    // Approval routes - Will be enabled after creating ApprovalController
    Route::prefix('approvals')->group(function () {
        Route::get('/pending', [App\Http\Controllers\ApprovalController::class, 'pending']);
        Route::get('/{approval}', [App\Http\Controllers\ApprovalController::class, 'show']);
        Route::post('/{approval}/approve', [App\Http\Controllers\ApprovalController::class, 'approve']);
        Route::post('/{approval}/reject', [App\Http\Controllers\ApprovalController::class, 'reject']);
        Route::post('/{approval}/delegate', [App\Http\Controllers\ApprovalController::class, 'delegate']);
    });
    */
});
