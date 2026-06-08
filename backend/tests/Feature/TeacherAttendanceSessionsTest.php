<?php

use App\Models\CargaAcademica;
use App\Models\Curso;
use App\Models\Docente;
use App\Models\Grado;
use App\Models\PeriodoAcademico;
use App\Models\Seccion;
use App\Models\User;
use App\Modules\Asistencia\Domain\Models\AsistenciaDocente;
use App\Modules\Asistencia\Domain\Models\MovimientoAsistencia;
use App\Modules\Asistencia\Domain\Models\SesionClase;
use App\Modules\Asistencia\Domain\Services\TeacherAttendanceSessionService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function teacherAttendanceManager(): User
{
    $user = User::factory()->create();
    $user->givePermissionTo('gestionar_planilla');

    return $user;
}

function teacherCoordinator(): User
{
    $user = User::factory()->create();
    $user->assignRole('coordinador_academico');

    return $user;
}

function teacherAcademicAssignment(User $operator, ?Docente $teacher = null): CargaAcademica
{
    $period = PeriodoAcademico::factory()->create(['estado' => 'borrador', 'creado_por' => $operator->id]);
    $grade = Grado::create([
        'periodo_academico_id' => $period->id,
        'nombre' => 'Docente '.fake()->unique()->numberBetween(100, 999),
        'nivel' => 'secundaria',
        'orden' => 1,
        'activo' => true,
    ]);
    $section = Seccion::create([
        'grado_id' => $grade->id,
        'nombre' => 'D'.fake()->unique()->numberBetween(100, 999),
        'turno' => 'mañana',
        'activo' => true,
    ]);

    return CargaAcademica::create([
        'seccion_id' => $section->id,
        'curso_id' => Curso::factory()->create()->id,
        'docente_id' => ($teacher ?? Docente::factory()->create())->id,
        'vigente_desde' => '2026-03-01',
        'activo' => true,
        'asignado_por' => $operator->id,
    ]);
}

it('creates sessions from assignments and calculates tardiness using the first class', function () {
    $manager = teacherAttendanceManager();
    $assignment = teacherAcademicAssignment($manager);
    $service = app(TeacherAttendanceSessionService::class);

    $session = $service->createSessionFromAssignment($assignment, Carbon::parse('2026-06-08'), '08:00:00', '09:30:00');
    $attendance = $service->registerEntry($assignment->docente, Carbon::parse('2026-06-08 08:15:00'), $manager);

    expect($session)->toBeInstanceOf(SesionClase::class)
        ->and($attendance->estado)->toBe('presente')
        ->and($attendance->primer_ingreso)->toBe('08:15:00')
        ->and($attendance->minutos_tardanza)->toBe(15)
        ->and(MovimientoAsistencia::where('asistencia_docente_id', $attendance->id)->count())->toBe(1);
});

it('marks an absent teacher when the class ends without attendance', function () {
    $manager = teacherAttendanceManager();
    $assignment = teacherAcademicAssignment($manager);
    $service = app(TeacherAttendanceSessionService::class);
    $session = $service->createSessionFromAssignment($assignment, Carbon::parse('2026-06-08'), '08:00:00', '09:30:00');

    $result = $service->closeEndedSessions(Carbon::parse('2026-06-08 09:31:00'), $manager);

    expect($result)->toBe(['sessions_reviewed' => 1, 'teacher_absences_created' => 1])
        ->and($session->refresh()->estado)->toBe('docente_ausente')
        ->and(AsistenciaDocente::firstOrFail()->estado)->toBe('falta_injustificada');
});

it('cancels a class session and cancellation avoids creating teacher absence', function () {
    $manager = teacherAttendanceManager();
    $coordinator = teacherCoordinator();
    $assignment = teacherAcademicAssignment($manager);
    $service = app(TeacherAttendanceSessionService::class);
    $session = $service->createSessionFromAssignment($assignment, Carbon::parse('2026-06-08'), '08:00:00', '09:30:00');

    $this->actingAs($coordinator)
        ->postJson("/api/v1/class-sessions/{$session->id}/cancellation", ['reason' => 'Actividad institucional aprobada.'])
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelada');

    $result = $service->closeEndedSessions(Carbon::parse('2026-06-08 09:31:00'), $manager);

    expect($result)->toBe(['sessions_reviewed' => 0, 'teacher_absences_created' => 0])
        ->and(AsistenciaDocente::count())->toBe(0);
});

it('assigns a substitute teacher and evaluates attendance against the substitute', function () {
    $manager = teacherAttendanceManager();
    $original = Docente::factory()->create();
    $substitute = Docente::factory()->create();
    $assignment = teacherAcademicAssignment($manager, $original);
    $service = app(TeacherAttendanceSessionService::class);
    $session = $service->createSessionFromAssignment($assignment, Carbon::parse('2026-06-08'), '08:00:00', '09:30:00');

    $this->actingAs($manager)
        ->putJson("/api/v1/class-sessions/{$session->id}/substitute", ['teacher_id' => $substitute->id])
        ->assertOk()
        ->assertJsonPath('data.substitute_teacher_id', $substitute->id);

    $service->closeEndedSessions(Carbon::parse('2026-06-08 09:31:00'), $manager);

    expect(AsistenciaDocente::firstOrFail()->docente_id)->toBe($substitute->id)
        ->and(AsistenciaDocente::firstOrFail()->docente_sustituto_id)->toBe($substitute->id);
});

it('allows payroll adjustments but prevents a teacher from correcting their own attendance', function () {
    $manager = teacherAttendanceManager();
    $teacherUser = User::factory()->create();
    $teacherUser->givePermissionTo('gestionar_planilla');
    $teacherUser->assignRole('docente');
    $teacher = Docente::factory()->create(['user_id' => $teacherUser->id]);

    $this->actingAs($manager)
        ->postJson('/api/v1/teacher-attendance/adjustments', [
            'teacher_id' => $teacher->id,
            'date' => '2026-06-08',
            'adjustment_type' => 'add',
            'minutes' => 10,
            'reason' => 'Corrección aprobada por planilla.',
        ])
        ->assertCreated()
        ->assertJsonPath('data.late_minutes', 10);

    $this->actingAs($teacherUser)
        ->postJson('/api/v1/teacher-attendance/adjustments', [
            'teacher_id' => $teacher->id,
            'date' => '2026-06-08',
            'adjustment_type' => 'subtract',
            'minutes' => 5,
            'reason' => 'Intento de autocorrección.',
        ])
        ->assertForbidden();
});

it('returns stable auth, validation, not found, conflict, and forbidden errors for teacher sessions', function () {
    $manager = teacherAttendanceManager();
    $coordinator = teacherCoordinator();
    $assignment = teacherAcademicAssignment($manager);
    $session = app(TeacherAttendanceSessionService::class)
        ->createSessionFromAssignment($assignment, Carbon::parse('2026-06-08'), '08:00:00', '09:30:00');

    $this->getJson('/api/v1/teacher-attendance')->assertUnauthorized();

    $this->actingAs($manager)
        ->postJson('/api/v1/teacher-attendance/adjustments', [])
        ->assertUnprocessable();

    $this->actingAs($manager)
        ->putJson('/api/v1/class-sessions/00000000-0000-7000-8000-000000000000/substitute', ['teacher_id' => $assignment->docente_id])
        ->assertNotFound();

    $this->actingAs($coordinator)
        ->postJson("/api/v1/class-sessions/{$session->id}/cancellation", ['reason' => 'Cancelada.'])
        ->assertOk();

    $this->actingAs($manager)
        ->putJson("/api/v1/class-sessions/{$session->id}/substitute", ['teacher_id' => $assignment->docente_id])
        ->assertConflict();

    $this->actingAs(User::factory()->create())
        ->getJson('/api/v1/teacher-attendance')
        ->assertForbidden();
});
