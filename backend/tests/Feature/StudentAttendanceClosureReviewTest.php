<?php

use App\Modules\Academico\Infrastructure\Models\Grado;
use App\Modules\Academico\Infrastructure\Models\Matricula;
use App\Modules\Academico\Infrastructure\Models\PeriodoAcademico;
use App\Modules\Academico\Infrastructure\Models\Seccion;
use App\Modules\Asistencia\Domain\Models\AnomaliaAsistencia;
use App\Modules\Asistencia\Domain\Models\AsistenciaAlumno;
use App\Modules\Asistencia\Domain\Models\CamaraEstacion;
use App\Modules\Asistencia\Domain\Models\CuentaTecnica;
use App\Modules\Asistencia\Domain\Models\EstacionBiometrica;
use App\Modules\Asistencia\Domain\Models\EventoReconocimiento;
use App\Modules\Asistencia\Domain\Models\MovimientoAsistencia;
use App\Modules\Usuarios\Infrastructure\Models\Alumno;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function closureManager(string $role = 'auxiliar'): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

function enrolledStudent(?User $registrar = null): Alumno
{
    $registrar ??= closureManager();
    $student = Alumno::factory()->create(['user_id' => User::factory()->create()->id]);
    $period = PeriodoAcademico::factory()->create(['estado' => 'borrador', 'creado_por' => $registrar->id]);
    $grade = Grado::create([
        'periodo_academico_id' => $period->id,
        'nombre' => 'Primero '.fake()->unique()->numberBetween(100, 999),
        'nivel' => 'primaria',
        'orden' => 1,
        'activo' => true,
    ]);
    $section = Seccion::create([
        'grado_id' => $grade->id,
        'nombre' => 'A'.fake()->unique()->numberBetween(100, 999),
        'turno' => 'mañana',
        'aula' => '101',
        'capacidad' => 30,
        'activo' => true,
    ]);
    Matricula::create([
        'alumno_id' => $student->id,
        'seccion_id' => $section->id,
        'codigo' => 'MAT-'.fake()->unique()->numerify('######'),
        'fecha' => '2026-03-01',
        'estado' => 'activo',
        'registrado_por' => $registrar->id,
    ]);

    return $student;
}

it('closes the day by creating unjustified absences for active students without attendance', function () {
    $manager = closureManager();
    $student = enrolledStudent($manager);

    $this->actingAs($manager)
        ->withHeader('Idempotency-Key', 'closure-2026-06-07')
        ->postJson('/api/v1/student-attendance/day-closures', ['date' => '2026-06-07'])
        ->assertAccepted()
        ->assertJsonPath('data.status', 'queued')
        ->assertJsonPath('data.absences_created', 1)
        ->assertJsonPath('data.anomalies_created', 0);

    $this->assertDatabaseHas('asistencias_alumnos', [
        'alumno_id' => $student->id,
        'fecha' => '2026-06-07',
        'estado' => 'falta_injustificada',
        'presencia_abierta' => false,
    ]);
});

it('marks a late event already captured before closure as tardiness instead of unjustified absence', function () {
    $manager = closureManager();
    $student = enrolledStudent($manager);
    AsistenciaAlumno::create([
        'alumno_id' => $student->id,
        'fecha' => '2026-06-07',
        'estado' => 'falta_injustificada',
        'primer_ingreso' => '08:10:00',
        'presencia_abierta' => true,
        'registrado_por' => $manager->id,
    ]);

    $this->actingAs($manager)
        ->withHeader('Idempotency-Key', 'closure-late-2026-06-07')
        ->postJson('/api/v1/student-attendance/day-closures', ['date' => '2026-06-07'])
        ->assertAccepted()
        ->assertJsonPath('data.absences_created', 0);

    expect(AsistenciaAlumno::firstOrFail()->estado)->toBe('tardanza');
});

it('creates a pending anomaly when a student has an entry without exit at closure', function () {
    $manager = closureManager();
    $student = enrolledStudent($manager);
    $attendance = AsistenciaAlumno::create([
        'alumno_id' => $student->id,
        'fecha' => '2026-06-07',
        'estado' => 'presente',
        'primer_ingreso' => '07:35:00',
        'presencia_abierta' => true,
        'registrado_por' => $manager->id,
    ]);

    $this->actingAs($manager)
        ->withHeader('Idempotency-Key', 'closure-anomaly-2026-06-07')
        ->postJson('/api/v1/student-attendance/day-closures', ['date' => '2026-06-07'])
        ->assertAccepted()
        ->assertJsonPath('data.anomalies_created', 1);

    $this->assertDatabaseHas('anomalias_asistencia', [
        'asistencia_alumno_id' => $attendance->id,
        'tipo' => 'sin_salida',
        'estado' => 'pendiente',
    ]);
    expect($attendance->refresh()->ultima_salida)->toBeNull();
});

it('allows auxiliary users to resolve anomalies and rejects toe resolution attempts', function () {
    $manager = closureManager();
    $toe = closureManager('toe');
    $student = enrolledStudent($manager);
    $attendance = AsistenciaAlumno::create([
        'alumno_id' => $student->id,
        'fecha' => '2026-06-07',
        'estado' => 'presente',
        'presencia_abierta' => true,
        'registrado_por' => $manager->id,
    ]);
    $anomaly = AnomaliaAsistencia::create([
        'asistencia_alumno_id' => $attendance->id,
        'tipo' => 'sin_salida',
        'estado' => 'pendiente',
        'detalle' => 'Ingreso sin salida.',
        'asignado_a' => $manager->id,
    ]);

    $this->actingAs($toe)
        ->postJson("/api/v1/student-attendance/anomalies/{$anomaly->id}/resolution", ['reason' => 'Intento TOE.'])
        ->assertForbidden();

    $this->actingAs($manager)
        ->postJson("/api/v1/student-attendance/anomalies/{$anomaly->id}/resolution", ['reason' => 'Salida verificada en cuaderno de incidencias.'])
        ->assertOk()
        ->assertJsonPath('data.status', 'resuelta');
});

it('justifies absences and alerts only students with repeated unjustified absences', function () {
    $manager = closureManager();
    $toe = closureManager('toe');
    $unjustified = enrolledStudent($manager);
    $justified = enrolledStudent($manager);

    foreach (['2026-06-03', '2026-06-04', '2026-06-05'] as $date) {
        AsistenciaAlumno::create(['alumno_id' => $unjustified->id, 'fecha' => $date, 'estado' => 'falta_injustificada', 'presencia_abierta' => false, 'registrado_por' => $manager->id]);
        AsistenciaAlumno::create(['alumno_id' => $justified->id, 'fecha' => $date, 'estado' => 'falta_justificada', 'presencia_abierta' => false, 'registrado_por' => $manager->id]);
    }

    $absence = AsistenciaAlumno::where('alumno_id', $unjustified->id)->firstOrFail();
    $this->actingAs($toe)
        ->postJson("/api/v1/student-attendance/absences/{$absence->id}/justification", ['reason' => 'Constancia médica recibida.'])
        ->assertOk()
        ->assertJsonPath('data.status', 'falta_justificada');

    $this->actingAs($toe)
        ->getJson('/api/v1/student-attendance/alerts')
        ->assertOk()
        ->assertJsonCount(0, 'data');

    AsistenciaAlumno::create(['alumno_id' => $unjustified->id, 'fecha' => '2026-06-06', 'estado' => 'falta_injustificada', 'presencia_abierta' => false, 'registrado_por' => $manager->id]);

    $this->actingAs($toe)
        ->getJson('/api/v1/student-attendance/alerts')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.student_id', $unjustified->id)
        ->assertJsonPath('data.0.unjustified_absences', 3);
});

it('reviews pending recognition events by reassigning them to the selected student', function () {
    $manager = closureManager();
    $student = enrolledStudent($manager);
    $technicalAccount = CuentaTecnica::create([
        'nombre' => 'Estación revisión',
        'tipo' => 'estacion_web',
        'token_hash' => hash('sha256', 'review-token'),
        'scopes' => ['attendance:capture'],
        'activo' => true,
        'creado_por' => $manager->id,
    ]);
    $station = EstacionBiometrica::create([
        'codigo' => 'ST-REVIEW-001',
        'nombre' => 'Puerta revisión',
        'ubicacion' => 'Ingreso principal',
        'tipo_equipo' => 'pc',
        'cuenta_tecnica_id' => $technicalAccount->id,
        'activo' => true,
        'configuracion' => ['mode' => 'mixed'],
    ]);
    $camera = CamaraEstacion::create([
        'estacion_id' => $station->id,
        'device_id_navegador' => 'review-camera',
        'nombre' => 'Cámara revisión',
        'modo' => 'entrada',
        'activo' => true,
    ]);
    $event = EventoReconocimiento::create([
        'idempotency_key' => 'review-event-001',
        'estacion_id' => $station->id,
        'camara_estacion_id' => $camera->id,
        'cuenta_tecnica_id' => $technicalAccount->id,
        'tipo_persona' => 'desconocido',
        'user_id' => null,
        'capturado_en' => '2026-06-07 07:40:00',
        'recibido_en' => '2026-06-07 07:40:05',
        'confianza' => 0.5,
        'prueba_vida_superada' => true,
        'estado' => 'pendiente_revision',
        'motivo_estado' => 'Revisión manual requerida.',
    ]);

    $this->actingAs($manager)
        ->postJson("/api/v1/recognition-events/{$event->id}/review", [
            'outcome' => 'reassigned',
            'matched_student_id' => $student->id,
            'reason' => 'Coincidencia confirmada por auxiliar.',
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'aceptado');

    expect(MovimientoAsistencia::count())->toBe(1)
        ->and(AsistenciaAlumno::firstOrFail()->alumno_id)->toBe($student->id)
        ->and(EventoReconocimiento::firstOrFail()->revisado_por)->toBe($manager->id);
});

it('returns stable auth, validation, not found, and conflict errors for closure review flows', function () {
    $manager = closureManager();
    $student = enrolledStudent($manager);
    $attendance = AsistenciaAlumno::create([
        'alumno_id' => $student->id,
        'fecha' => '2026-06-07',
        'estado' => 'presente',
        'presencia_abierta' => false,
        'registrado_por' => $manager->id,
    ]);
    $resolved = AnomaliaAsistencia::create([
        'asistencia_alumno_id' => $attendance->id,
        'tipo' => 'sin_salida',
        'estado' => 'resuelta',
        'detalle' => 'Ya resuelta.',
        'asignado_a' => $manager->id,
        'resuelto_por' => $manager->id,
        'resolucion' => 'Atendida.',
        'resuelto_en' => now(),
    ]);

    $this->postJson('/api/v1/student-attendance/day-closures', ['date' => '2026-06-07'])
        ->assertUnauthorized();

    $this->actingAs($manager)
        ->postJson('/api/v1/student-attendance/day-closures', [])
        ->assertUnprocessable();

    $this->actingAs($manager)
        ->postJson('/api/v1/student-attendance/anomalies/00000000-0000-7000-8000-000000000000/resolution', ['reason' => 'No existe.'])
        ->assertNotFound();

    $this->actingAs($manager)
        ->postJson("/api/v1/student-attendance/anomalies/{$resolved->id}/resolution", ['reason' => 'Duplicada.'])
        ->assertConflict();

    $this->actingAs($manager)
        ->postJson("/api/v1/student-attendance/absences/{$attendance->id}/justification", ['reason' => 'No es falta.'])
        ->assertConflict();
});
