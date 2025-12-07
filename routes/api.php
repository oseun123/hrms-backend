<?php

use Illuminate\Http\Request;
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
    Route::post('/register', [App\Http\Controllers\Auth\AuthController::class, 'register']);
    Route::post('/password/reset-request', [App\Http\Controllers\Auth\PasswordResetController::class, 'requestReset']);
    Route::post('/password/reset', [App\Http\Controllers\Auth\PasswordResetController::class, 'reset']);
});

// Public tenant lookup
Route::get('/tenants/{slug}', [App\Http\Controllers\TenantController::class, 'getBySlug']);


// Protected routes
Route::middleware('auth:sanctum')->group(function () {

    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [App\Http\Controllers\Auth\AuthController::class, 'logout']);
        Route::get('/me', [App\Http\Controllers\Auth\AuthController::class, 'me']);
    });

    // Test protected endpoint
    Route::get('/test', function () {
        return response()->json([
            'success' => true,
            'message' => 'Protected endpoint works!',
            'user' => auth()->user(),
        ]);
    });

    // HRIS Module routes
    Route::prefix('hris')->group(function () {

        // Departments
        Route::apiResource('departments', App\Http\Controllers\HRIS\DepartmentController::class);

        // Levels
        Route::apiResource('levels', App\Http\Controllers\HRIS\LevelController::class);

        // Grades
        Route::apiResource('grades', App\Http\Controllers\HRIS\GradeController::class);

        // Positions
        Route::apiResource('positions', App\Http\Controllers\HRIS\PositionController::class);

        // Employees
        Route::apiResource('employees', App\Http\Controllers\HRIS\EmployeeController::class);
        Route::prefix('employees/{employee}')->group(function () {
            // Employment Details
            Route::post('/employment-details', [App\Http\Controllers\HRIS\EmployeeController::class, 'createEmploymentDetails']);
            Route::get('/employment-details', [App\Http\Controllers\HRIS\EmployeeController::class, 'employmentDetails']);
            Route::put('/employment-details', [App\Http\Controllers\HRIS\EmployeeController::class, 'updateEmploymentDetails']);

            // Contact Details
            Route::post('/contact-details', [App\Http\Controllers\HRIS\EmployeeController::class, 'createContactDetails']);
            Route::get('/contact-details', [App\Http\Controllers\HRIS\EmployeeController::class, 'contactDetails']);
            Route::put('/contact-details', [App\Http\Controllers\HRIS\EmployeeController::class, 'updateContactDetails']);

            // Financial Details (handled by dedicated controller)
            Route::get('/financial-details', [App\Http\Controllers\HRIS\EmployeeController::class, 'financialDetails']);
            Route::put('/financial-details', [App\Http\Controllers\HRIS\EmployeeController::class, 'updateFinancialDetails']);

            // Medical Details (handled by dedicated controller)
            Route::get('/medical-details', [App\Http\Controllers\HRIS\EmployeeController::class, 'medicalDetails']);
            Route::put('/medical-details', [App\Http\Controllers\HRIS\EmployeeController::class, 'updateMedicalDetails']);

            // Other routes
            Route::get('/profile-completeness', [App\Http\Controllers\HRIS\EmployeeController::class, 'profileCompleteness']);
            Route::get('/history', [App\Http\Controllers\HRIS\EmployeeController::class, 'history']);
        });

        // Employee Detail Routes
        Route::prefix('employees/{employee}')->group(function () {
            // Financial Details
            Route::post('/financial-details', [App\Http\Controllers\HRIS\FinancialDetailsController::class, 'store']);
            Route::put('/financial-details', [App\Http\Controllers\HRIS\FinancialDetailsController::class, 'update']);
            Route::get('/financial-details', [App\Http\Controllers\HRIS\FinancialDetailsController::class, 'show']);

            // Medical Details
            Route::post('/medical-details', [App\Http\Controllers\HRIS\MedicalDetailsController::class, 'store']);
            Route::put('/medical-details', [App\Http\Controllers\HRIS\MedicalDetailsController::class, 'update']);
            Route::get('/medical-details', [App\Http\Controllers\HRIS\MedicalDetailsController::class, 'show']);

            // Addresses
            Route::get('/addresses', [App\Http\Controllers\HRIS\AddressController::class, 'index']);
            Route::post('/addresses', [App\Http\Controllers\HRIS\AddressController::class, 'store']);
            Route::put('/addresses/{address}', [App\Http\Controllers\HRIS\AddressController::class, 'update']);
            Route::delete('/addresses/{address}', [App\Http\Controllers\HRIS\AddressController::class, 'destroy']);

            // Emergency Contacts
            Route::get('/emergency-contacts', [App\Http\Controllers\HRIS\EmergencyContactController::class, 'index']);
            Route::post('/emergency-contacts', [App\Http\Controllers\HRIS\EmergencyContactController::class, 'store']);
            Route::put('/emergency-contacts/{contact}', [App\Http\Controllers\HRIS\EmergencyContactController::class, 'update']);
            Route::delete('/emergency-contacts/{contact}', [App\Http\Controllers\HRIS\EmergencyContactController::class, 'destroy']);

            // Education
            Route::get('/education', [App\Http\Controllers\HRIS\EducationController::class, 'index']);
            Route::post('/education', [App\Http\Controllers\HRIS\EducationController::class, 'store']);
            Route::put('/education/{education}', [App\Http\Controllers\HRIS\EducationController::class, 'update']);
            Route::delete('/education/{education}', [App\Http\Controllers\HRIS\EducationController::class, 'destroy']);

            // Dependents
            Route::get('/dependents', [App\Http\Controllers\HRIS\DependentController::class, 'index']);
            Route::post('/dependents', [App\Http\Controllers\HRIS\DependentController::class, 'store']);
            Route::put('/dependents/{dependent}', [App\Http\Controllers\HRIS\DependentController::class, 'update']);
            Route::delete('/dependents/{dependent}', [App\Http\Controllers\HRIS\DependentController::class, 'destroy']);
        });

        // Documents
        Route::prefix('employees/{employee}/documents')->group(function () {
            Route::get('/', [App\Http\Controllers\HRIS\DocumentController::class, 'index']);
            Route::post('/', [App\Http\Controllers\HRIS\DocumentController::class, 'store']);
            Route::get('/{document}', [App\Http\Controllers\HRIS\DocumentController::class, 'show']);
            Route::delete('/{document}', [App\Http\Controllers\HRIS\DocumentController::class, 'destroy']);
        });

        // Skills
        Route::apiResource('skills', App\Http\Controllers\HRIS\SkillController::class);

        /* 
        // Reports - Will be enabled after creating ReportController
        Route::prefix('reports')->group(function () {
            Route::get('/headcount', [App\Http\Controllers\HRIS\ReportController::class, 'headcount']);
            Route::get('/demographics', [App\Http\Controllers\HRIS\ReportController::class, 'demographics']);
            Route::get('/profile-completion', [App\Http\Controllers\HRIS\ReportController::class, 'profileCompletion']);
        });
        */
    });

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
