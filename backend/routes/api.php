<?php

use App\Modules\Academico\Presentation\Controllers\AcademicController;
use App\Modules\Academico\Presentation\Controllers\AcademicReportController;
use App\Modules\Academico\Presentation\Controllers\AssessmentController;
use App\Modules\Academico\Presentation\Controllers\NotasController;
use App\Modules\Asistencia\Presentation\Controllers\StationController;
use App\Modules\Asistencia\Presentation\Controllers\StudentAttendanceController;
use App\Modules\Asistencia\Presentation\Controllers\TeacherAttendanceController;
use App\Modules\Auth\Presentation\Controllers\PasswordRecoveryController;
use App\Modules\Auth\Presentation\Controllers\SessionController;
use App\Modules\Finanzas\Presentation\Controllers\FinanceConfigController;
use App\Modules\Finanzas\Presentation\Controllers\TeacherPayrollController;
use App\Modules\Usuarios\Presentation\Controllers\AccountController;
use App\Modules\Usuarios\Presentation\Controllers\BiometricController;
use App\Modules\Usuarios\Presentation\Controllers\FamilyLinkController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', fn () => response()->json([
        'data' => ['status' => 'ok'],
    ]))->name('api.v1.health');

    Route::post('station-activations', [StationController::class, 'activate'])->middleware('throttle:station-activation');

    Route::middleware(['station.session'])->group(function (): void {
        Route::get('station/session', [StationController::class, 'session']);
        Route::post('station/captures', [StationController::class, 'capture'])->middleware(['throttle:station-capture', 'idempotent']);
    });

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

        // Comunicados y Notificaciones
        // Horarios y Calendario
        Route::get('/schedules', [\App\Modules\Horarios\Presentation\Controllers\ScheduleController::class, 'listSchedules']);
        Route::post('/schedules', [\App\Modules\Horarios\Presentation\Controllers\ScheduleController::class, 'createSchedule']);
        Route::get('/calendar-events', [\App\Modules\Horarios\Presentation\Controllers\ScheduleController::class, 'listCalendarEvents']);
        Route::post('/calendar-events', [\App\Modules\Horarios\Presentation\Controllers\ScheduleController::class, 'createCalendarEvent']);

        // Estaciones
        Route::get('stations', [StationController::class, 'index']);
        Route::post('stations', [StationController::class, 'store']);
        Route::patch('stations/{stationId}', [StationController::class, 'update']);
        Route::post('stations/{stationId}/activation-codes', [StationController::class, 'activationCode'])->middleware('throttle:station-activation');
        Route::post('stations/{stationId}/revocation', [StationController::class, 'revoke']);
        Route::get('stations/{stationId}/cameras', [StationController::class, 'cameras']);
        Route::post('stations/{stationId}/cameras', [StationController::class, 'storeCamera']);

        // Asistencia Alumnos
        Route::get('student-attendance', [StudentAttendanceController::class, 'index']);
        Route::post('student-attendance/manual-events', [StudentAttendanceController::class, 'manual'])->middleware('idempotent');
        Route::post('student-attendance/day-closures', [StudentAttendanceController::class, 'closeDay'])->middleware('idempotent');
        Route::get('student-attendance/anomalies', [StudentAttendanceController::class, 'anomalies']);
        Route::post('student-attendance/anomalies/{anomalyId}/resolution', [StudentAttendanceController::class, 'resolveAnomaly']);
        Route::post('student-attendance/absences/{attendanceId}/justification', [StudentAttendanceController::class, 'justifyAbsence']);
        Route::get('student-attendance/alerts', [StudentAttendanceController::class, 'alerts']);
        Route::get('recognition-events', [StudentAttendanceController::class, 'recognitionEvents']);
        Route::post('recognition-events/{recognitionEventId}/review', [StudentAttendanceController::class, 'reviewRecognition']);

        // Asistencia Docentes
        Route::get('teacher-attendance', [TeacherAttendanceController::class, 'index']);
        Route::post('teacher-attendance/adjustments', [TeacherAttendanceController::class, 'adjustment']);
        Route::post('class-sessions/{classSessionId}/cancellation', [TeacherAttendanceController::class, 'cancel']);
        Route::put('class-sessions/{classSessionId}/substitute', [TeacherAttendanceController::class, 'substitute']);

        // Nómina Docente (Finanzas)
        Route::get('payment-concepts', [FinanceConfigController::class, 'concepts']);
        Route::post('payment-concepts', [FinanceConfigController::class, 'storeConcept']);
        Route::patch('payment-concepts/{conceptId}', [FinanceConfigController::class, 'updateConcept']);
        Route::get('student-benefits', [FinanceConfigController::class, 'benefits']);
        Route::post('student-benefits', [FinanceConfigController::class, 'storeBenefit']);
        Route::post('student-benefits/{benefitId}/deactivation', [FinanceConfigController::class, 'deactivateBenefit']);

        Route::get('teacher-payroll/rates', [TeacherPayrollController::class, 'rates']);
        Route::post('teacher-payroll/rates', [TeacherPayrollController::class, 'storeRate']);
        Route::get('teacher-payroll/liquidations', [TeacherPayrollController::class, 'liquidations']);
        Route::post('teacher-payroll/liquidations', [TeacherPayrollController::class, 'storeLiquidation']);
        Route::post('teacher-payroll/liquidations/{liquidationId}/closure', [TeacherPayrollController::class, 'closeLiquidation']);
        Route::post('teacher-payroll/reports', [TeacherPayrollController::class, 'report']);

        // Biometría
        Route::get('biometric-consents', [BiometricController::class, 'index']);
        Route::post('biometric-consents', [BiometricController::class, 'store']);
        Route::post('biometric-consents/{consentId}/revocation', [BiometricController::class, 'revoke']);
        Route::post('biometric-enrollments', [BiometricController::class, 'enroll']);

        // Assessments
        Route::get('assessments', [AssessmentController::class, 'index']);
        Route::post('assessments', [AssessmentController::class, 'store'])->name('api.v1.assessments.store');
        Route::post('assessments/{examen}/publication', [AcademicReportController::class, 'publishAssessment']);
        Route::put('assessments/{examen}/closure', [AcademicReportController::class, 'setAssessmentClosure']);

        // Academic Reports & Rankings
        Route::get('rankings', [AcademicReportController::class, 'listRankings']);
        Route::post('academic-reports', [AcademicReportController::class, 'generateAcademicReport']);
        Route::post('assessment-results/{nota}/corrections', [AcademicReportController::class, 'correctPublishedAssessmentResult']);

        // Notas (Result Entry Import)
        Route::post('assessments/{examen}/grades', [NotasController::class, 'store']);
        Route::post('assessments/{examen}/grades/import', [NotasController::class, 'import']);
        Route::put('grades/{nota}', [NotasController::class, 'update']);
    });
});
