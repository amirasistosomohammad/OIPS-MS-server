<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\BackupController;
use App\Http\Controllers\Api\BeneficiaryController;
use App\Http\Controllers\Api\BeneficiaryNotificationController;
use App\Http\Controllers\Api\ProgramController;
use App\Http\Controllers\Api\ProgramEnrollmentController;
use App\Http\Controllers\Api\ProgramMetadataController;
use App\Http\Controllers\Api\ProgramUpdateEntryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ReportsController;
use App\Http\Controllers\Api\SystemSettingsController;
use App\Http\Controllers\Api\UserManagementController;
use App\Http\Controllers\Api\UserSecurityController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::get('settings', [SystemSettingsController::class, 'public']);
Route::get('assets/{path}', [SystemSettingsController::class, 'asset'])->where('path', '.*');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::apiResource('users', UserManagementController::class);
    Route::get('dashboard', [DashboardController::class, 'index']);
    Route::apiResource('programs', ProgramController::class);
    Route::apiResource('beneficiaries', BeneficiaryController::class);
    Route::apiResource('enrollments', ProgramEnrollmentController::class)->parameter('enrollments', 'enrollment');
    Route::get('program-updates', [ProgramUpdateEntryController::class, 'index']);
    Route::post('program-updates', [ProgramUpdateEntryController::class, 'store']);
    Route::delete('program-updates/{programUpdate}', [ProgramUpdateEntryController::class, 'destroy']);
    Route::get('notifications', [BeneficiaryNotificationController::class, 'index']);
    Route::post('notifications/{notification}/read', [BeneficiaryNotificationController::class, 'markAsRead']);
    Route::delete('notifications/{notification}', [BeneficiaryNotificationController::class, 'destroy']);
    Route::get('metadata/status-options', [ProgramMetadataController::class, 'statusOptions']);
    Route::get('metadata/field-templates', [ProgramMetadataController::class, 'fieldTemplates']);
    Route::get('reports/summary', [ReportsController::class, 'summary']);
    Route::get('activity-logs', [ActivityLogController::class, 'index']);
    Route::get('activity-logs/{activityLog}', [ActivityLogController::class, 'show']);
    Route::put('user/password', [UserSecurityController::class, 'updatePassword']);
    Route::put('admin/settings', [SystemSettingsController::class, 'update']);
    Route::post('admin/settings/logo', [SystemSettingsController::class, 'uploadLogo']);
    Route::delete('admin/settings/logo/{slot}', [SystemSettingsController::class, 'removeLogo']);
    Route::post('admin/settings/auth-background', [SystemSettingsController::class, 'uploadAuthBackground']);
    Route::get('admin/backup', [BackupController::class, 'downloadNow']);
    Route::get('admin/backup/schedule', [BackupController::class, 'schedule']);
    Route::put('admin/backup/schedule', [BackupController::class, 'updateSchedule']);
    Route::get('admin/backup/list', [BackupController::class, 'list']);
    Route::get('admin/backup/download/latest', [BackupController::class, 'downloadLatest']);
    Route::get('admin/backup/download/file/{filename}', [BackupController::class, 'downloadFile']);
});
