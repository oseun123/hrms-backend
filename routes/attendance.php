<?php

use App\Http\Controllers\Attendance\AttendanceController;
use Illuminate\Support\Facades\Route;

Route::prefix('attendance')->group(function () {
    Route::get('status', [AttendanceController::class, 'checkStatus']);
    Route::post('clock', [AttendanceController::class, 'clock']);
    Route::post('reference-photo', [AttendanceController::class, 'uploadReferencePhoto']);
    
    Route::get('personal-overview', [AttendanceController::class, 'personalOverview']);
    Route::get('summary', [AttendanceController::class, 'summary']);
    
    Route::get('my-attendance', [AttendanceController::class, 'myAttendance']);
    Route::get('daily-attendance', [AttendanceController::class, 'dailyAttendance']);
    
    Route::get('settings/schedule', [AttendanceController::class, 'getWorkSchedule']);
    Route::post('settings/schedule', [AttendanceController::class, 'setWorkSchedule']);
    
    Route::get('settings/approval', [AttendanceController::class, 'getApprovalSetting']);
    Route::post('settings/approval', [AttendanceController::class, 'setApprovalSetting']);
    
    Route::post('employee_request', [AttendanceController::class, 'requestCorrection']);
    Route::get('employee_request/track', [AttendanceController::class, 'trackRequests']);
    Route::get('requests', [AttendanceController::class, 'getAttendanceRequests']);
    Route::post('approve-request/{id}', [AttendanceController::class, 'handleApproval']);
});
