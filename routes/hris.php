<?php

use Illuminate\Support\Facades\Route;

// HRIS Module routes
Route::prefix('hris')->group(function () {
    // Dashboard
    Route::prefix('dashboard')->group(function () {
        Route::get('/summary', [App\Http\Controllers\HRIS\DashboardController::class, 'summary']);
        Route::get('/team', [App\Http\Controllers\HRIS\DashboardController::class, 'team']);
        Route::get('/downlines', [App\Http\Controllers\HRIS\DashboardController::class, 'downlines']);
        Route::get('/notifications', [App\Http\Controllers\HRIS\DashboardController::class, 'notifications']);
        Route::get('/notifications/unread-count', [App\Http\Controllers\HRIS\DashboardController::class, 'unreadCount']);
        Route::patch('/notifications/{id}/read', [App\Http\Controllers\HRIS\DashboardController::class, 'markAsRead']);
        Route::patch('/notifications/read-all', [App\Http\Controllers\HRIS\DashboardController::class, 'markAllAsRead']);

        // Employee Tracking
        Route::get('/employees/on-probation', [App\Http\Controllers\HRIS\DashboardController::class, 'onProbation']);
        Route::get('/employees/birthdays-this-month', [App\Http\Controllers\HRIS\DashboardController::class, 'birthdaysThisMonth']);
        Route::get('/employees/anniversaries-this-month', [App\Http\Controllers\HRIS\DashboardController::class, 'anniversariesThisMonth']);
        Route::get('/analytics', [App\Http\Controllers\HRIS\DashboardController::class, 'analytics']);
        Route::get('/daily-quote', [App\Http\Controllers\HRIS\MotivationalQuoteController::class, 'getDailyQuote']);
    });

    // Document Types
    Route::get('document-types', [App\Http\Controllers\HRIS\DocumentTypeController::class, 'index']);

    // Departments
    Route::apiResource('departments', App\Http\Controllers\HRIS\DepartmentController::class);

    // Levels
    Route::apiResource('levels', App\Http\Controllers\HRIS\LevelController::class);

    // Grades
    Route::apiResource('grades', App\Http\Controllers\HRIS\GradeController::class);

    // Positions
    Route::apiResource('positions', App\Http\Controllers\HRIS\PositionController::class);

    // Branches
    Route::apiResource('branches', App\Http\Controllers\HRIS\BranchController::class);

    // Bulk Employees
    Route::prefix('employees-bulk')->group(function () {
        Route::get('/template', [\App\Http\Controllers\HRIS\BulkEmployeeController::class, 'downloadTemplate']);
        Route::post('/import', [\App\Http\Controllers\HRIS\BulkEmployeeController::class, 'import']);
    });

    // Employees - Users dropdown (must be before resource route)
    Route::get('employees/users-dropdown', [App\Http\Controllers\HRIS\EmployeeController::class, 'getUsersForDropdown']);

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

        // Work Experience
        Route::get('/work-experience', [App\Http\Controllers\HRIS\WorkExperienceController::class, 'index']);
        Route::post('/work-experience', [App\Http\Controllers\HRIS\WorkExperienceController::class, 'store']);
        Route::put('/work-experience/{experience}', [App\Http\Controllers\HRIS\WorkExperienceController::class, 'update']);
        Route::delete('/work-experience/{experience}', [App\Http\Controllers\HRIS\WorkExperienceController::class, 'destroy']);

        // Certifications
        Route::get('/certifications', [App\Http\Controllers\HRIS\CertificationController::class, 'index']);
        Route::post('/certifications', [App\Http\Controllers\HRIS\CertificationController::class, 'store']);
        Route::put('/certifications/{certification}', [App\Http\Controllers\HRIS\CertificationController::class, 'update']);
        Route::delete('/certifications/{certification}', [App\Http\Controllers\HRIS\CertificationController::class, 'destroy']);

        // Skills Assignment
        Route::get('/skills', [App\Http\Controllers\HRIS\EmployeeSkillController::class, 'index']);
        Route::post('/skills', [App\Http\Controllers\HRIS\EmployeeSkillController::class, 'store']);
        Route::put('/skills/{id}', [App\Http\Controllers\HRIS\EmployeeSkillController::class, 'update']);
        Route::delete('/skills/{id}', [App\Http\Controllers\HRIS\EmployeeSkillController::class, 'destroy']);

        // Other routes
        Route::get('/profile-completeness', [App\Http\Controllers\HRIS\EmployeeController::class, 'profileCompleteness']);
        Route::get('/history', [App\Http\Controllers\HRIS\EmployeeController::class, 'history']);
        Route::post('/photo', [App\Http\Controllers\HRIS\EmployeeController::class, 'updatePhoto']);
        Route::get('/audit-logs', [App\Http\Controllers\HRIS\EmployeeController::class, 'auditLogs']);
    });

    // Documents
    Route::prefix('employees/{employee}/documents')->group(function () {
        Route::get('/', [App\Http\Controllers\HRIS\DocumentController::class, 'index']);
        Route::post('/', [App\Http\Controllers\HRIS\DocumentController::class, 'store']);
        Route::get('/{document}', [App\Http\Controllers\HRIS\DocumentController::class, 'show']);
        Route::get('/{document}/download', [App\Http\Controllers\HRIS\DocumentController::class, 'download']);
        Route::delete('/{document}', [App\Http\Controllers\HRIS\DocumentController::class, 'destroy']);
    });

    Route::get('/employees/{employee}/profile-completeness-details', [App\Http\Controllers\HRIS\ProfileCompletenessController::class, 'show']);

    // Skills
    Route::get('skills/categories', [App\Http\Controllers\HRIS\SkillController::class, 'categories']);
    Route::apiResource('skills', App\Http\Controllers\HRIS\SkillController::class);

    // Profile Change Requests (Employee)
    Route::prefix('profile')->group(function () {
        Route::post('/change-requests', [App\Http\Controllers\HRIS\ProfileChangeRequestController::class, 'store']);
        Route::get('/my-requests', [App\Http\Controllers\HRIS\ProfileChangeRequestController::class, 'myRequests']);
        Route::delete('/change-requests/{id}', [App\Http\Controllers\HRIS\ProfileChangeRequestController::class, 'destroy']);
        Route::post('/report-incorrect-detail', [App\Http\Controllers\HRIS\IncorrectDetailReportController::class, 'store']);
        Route::post('/upload-temp', [App\Http\Controllers\HRIS\ProfileChangeRequestController::class, 'uploadTemp']);
    });

    // HR Approval Queue (HR only)
    Route::prefix('hr')->group(function () {
        Route::get('/approval-queue', [App\Http\Controllers\HRIS\HRApprovalQueueController::class, 'index']);
        Route::get('/approval-queue/{id}', [App\Http\Controllers\HRIS\HRApprovalQueueController::class, 'show']);
        Route::post('/approval-queue/{id}/approve', [App\Http\Controllers\HRIS\HRApprovalQueueController::class, 'approve']);
        Route::post('/approval-queue/{id}/decline', [App\Http\Controllers\HRIS\HRApprovalQueueController::class, 'decline']);

        Route::get('/incorrect-detail-reports', [App\Http\Controllers\HRIS\IncorrectDetailReportController::class, 'index']);
        Route::patch('/incorrect-detail-reports/{id}/resolve', [App\Http\Controllers\HRIS\IncorrectDetailReportController::class, 'resolve']);
        Route::patch('/incorrect-detail-reports/{id}/dismiss', [App\Http\Controllers\HRIS\IncorrectDetailReportController::class, 'dismiss']);
    });

    // Reports
    Route::prefix('reports')->group(function () {
        Route::get('/headcount-summary', [App\Http\Controllers\HRIS\ReportController::class, 'headcountSummary']);
        Route::get('/department-headcount', [App\Http\Controllers\HRIS\ReportController::class, 'departmentHeadcount']);
        Route::get('/demographics', [App\Http\Controllers\HRIS\ReportController::class, 'demographics']);
        Route::get('/employment', [App\Http\Controllers\HRIS\ReportController::class, 'employmentReport']);
        Route::get('/new-hires', [App\Http\Controllers\HRIS\ReportController::class, 'newHires']);
        Route::get('/attrition', [App\Http\Controllers\HRIS\ReportController::class, 'attrition']);
        Route::get('/document-expiry', [App\Http\Controllers\HRIS\ReportController::class, 'documentExpiry']);
        Route::get('/profile-completeness', [App\Http\Controllers\HRIS\ReportController::class, 'profileCompleteness']);
        Route::get('/financials', [App\Http\Controllers\HRIS\ReportController::class, 'financials']);
        Route::get('/medical', [App\Http\Controllers\HRIS\ReportController::class, 'medical']);
        Route::get('/contact', [App\Http\Controllers\HRIS\ReportController::class, 'contact']);
        Route::get('/skills-inventory', [App\Http\Controllers\HRIS\ReportController::class, 'skillsInventory']);
        Route::get('/birthday-anniversary', [App\Http\Controllers\HRIS\ReportController::class, 'birthdayAnniversary']);
        Route::get('/audit-trail', [App\Http\Controllers\HRIS\ReportController::class, 'auditTrail']);
        Route::get('/{type}/export', [App\Http\Controllers\HRIS\ReportController::class, 'export']);
    });

    // Employee Number Format
    Route::prefix('employee-number-format')->group(function () {
        Route::get('/', [App\Http\Controllers\HRIS\EmployeeNumberFormatController::class, 'show']);
        Route::put('/', [App\Http\Controllers\HRIS\EmployeeNumberFormatController::class, 'update']);
        Route::post('/preview', [App\Http\Controllers\HRIS\EmployeeNumberFormatController::class, 'preview']);
        Route::post('/regenerate', [App\Http\Controllers\HRIS\EmployeeNumberFormatController::class, 'regenerate']);
    });
});
