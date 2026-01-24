<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;

/*
|--------------------------------------------------------------------------
| Roles & Permissions Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'permission:preferences.roles_permissions'])->group(function () {
    Route::get('/roles', [RoleController::class, 'index']);
    Route::get('/roles/{role}', [RoleController::class, 'show']);
    Route::post('/roles', [RoleController::class, 'store']);
    Route::put('/roles/{role}', [RoleController::class, 'update']);
    Route::delete('/roles/{role}', [RoleController::class, 'destroy']);
    Route::post('/roles/{role}/sync-users', [RoleController::class, 'assignUsers']);
    Route::post('/users/{user}/sync-roles', [RoleController::class, 'syncUserRoles']);

    Route::get('/permissions', [PermissionController::class, 'index']);
});
