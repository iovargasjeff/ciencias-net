<?php

use App\Http\Controllers\Api\V1\Academic\AcademicController;
use App\Http\Controllers\Api\V1\Auth\PasswordRecoveryController;
use App\Http\Controllers\Api\V1\Auth\SessionController;
use App\Http\Controllers\Api\V1\Family\FamilyLinkController;
use App\Http\Controllers\Api\V1\IdentityAccess\AccountController;
use App\Modules\Academico\Presentation\Controllers\AssessmentController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', fn () => response()->json([
        'data' => ['status' => 'ok'],
    ]))->name('api.v1.health');

    Route::prefix('auth')->group(function (): void {
        Route::post('/login', [SessionController::class, 'store'])->middleware('throttle:human-login');
        Route::post('/forgot-password', [PasswordRecoveryController::class, 'requestLink'])
            ->middleware('throttle:password-recovery');
        Route::post('/reset-password', [PasswordRecoveryController::class, 'reset'])
            ->middleware('throttle:password-recovery');

        Route::middleware(['auth:sanctum', 'active.account'])->group(function (): void {
            Route::get('/session', [SessionController::class, 'show']);
            Route::post('/logout', [SessionController::class, 'destroy']);
        });
    });

    Route::middleware(['auth:sanctum', 'active.account'])->group(function (): void {
        Route::get('accounts', [AccountController::class, 'index']);
        Route::post('accounts', [AccountController::class, 'store']);
        Route::get('accounts/{accountId}', [AccountController::class, 'show']);
        Route::patch('accounts/{accountId}', [AccountController::class, 'update']);
        Route::put('accounts/{accountId}/activation', [AccountController::class, 'activation']);
        Route::put('accounts/{accountId}/roles', [AccountController::class, 'roles']);
        Route::post('accounts/{accountId}/password-reset', [AccountController::class, 'passwordReset']);

        Route::get('family-links', [FamilyLinkController::class, 'index']);
        Route::post('family-links', [FamilyLinkController::class, 'store']);
        Route::delete('family-links/{familyLinkId}', [FamilyLinkController::class, 'destroy']);
        Route::get('family/students', [FamilyLinkController::class, 'linkedStudents']);
        Route::get('family/students/{studentId}/summary', [FamilyLinkController::class, 'summary']);

        Route::get('academic-periods', [AcademicController::class, 'periods']);
        Route::post('academic-periods', [AcademicController::class, 'storePeriod'])->name('api.v1.academic-periods.store');
        Route::get('academic-periods/{academicPeriodId}', [AcademicController::class, 'showPeriod']);
        Route::patch('academic-periods/{academicPeriodId}', [AcademicController::class, 'updatePeriod'])->name('api.v1.academic-periods.update');
        Route::get('grades', [AcademicController::class, 'grades']);
        Route::post('grades', [AcademicController::class, 'storeGrade'])->name('api.v1.grades.store');
        Route::get('sections', [AcademicController::class, 'sections']);
        Route::post('sections', [AcademicController::class, 'storeSection'])->name('api.v1.sections.store');
        Route::get('courses', [AcademicController::class, 'courses']);
        Route::post('courses', [AcademicController::class, 'storeCourse'])->name('api.v1.courses.store');
        Route::get('enrollments', [AcademicController::class, 'enrollments']);
        Route::post('enrollments', [AcademicController::class, 'storeEnrollment'])->name('api.v1.enrollments.store');
        Route::get('teaching-assignments', [AcademicController::class, 'assignments']);
        Route::post('teaching-assignments', [AcademicController::class, 'storeAssignment'])->name('api.v1.teaching-assignments.store');

        // Assessments
        Route::get('assessments', [AssessmentController::class, 'index']);
        Route::post('assessments', [AssessmentController::class, 'store'])->name('api.v1.assessments.store');
    });
});
