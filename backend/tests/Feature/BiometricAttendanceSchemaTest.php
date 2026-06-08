<?php

use App\Modules\Academico\Infrastructure\Models\CargaAcademica;
use App\Modules\Academico\Infrastructure\Models\Curso;
use App\Modules\Academico\Infrastructure\Models\Grado;
use App\Modules\Academico\Infrastructure\Models\PeriodoAcademico;
use App\Modules\Academico\Infrastructure\Models\Seccion;
use App\Modules\Usuarios\Infrastructure\Models\Alumno;
use App\Modules\Usuarios\Infrastructure\Models\Docente;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function biometricSchemaOperator(): User
{
    return User::factory()->create();
}

function createTechnicalStation(User $operator): array
{
    $technicalAccountId = (string) Str::uuid();
    DB::table('cuentas_tecnicas')->insert([
        'id' => $technicalAccountId,
        'nombre' => 'Estación puerta principal',
        'tipo' => 'estacion_web',
        'token_hash' => hash('sha256', 'station-token'),
        'scopes' => json_encode(['station:capture']),
        'activo' => true,
        'creado_por' => $operator->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $stationId = (string) Str::uuid();
    DB::table('estaciones_biometricas')->insert([
        'id' => $stationId,
        'codigo' => 'puerta-principal',
        'nombre' => 'Puerta principal',
        'ubicacion' => 'Ingreso principal',
        'tipo_equipo' => 'pc',
        'cuenta_tecnica_id' => $technicalAccountId,
        'activo' => true,
        'configuracion' => json_encode(['acceptance_threshold' => 0.85]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $cameraId = (string) Str::uuid();
    DB::table('camaras_estacion')->insert([
        'id' => $cameraId,
        'estacion_id' => $stationId,
        'device_id_navegador' => 'camera-1',
        'nombre' => 'Cámara ingreso',
        'ubicacion' => 'Puerta',
        'modo' => 'bidireccional',
        'activo' => true,
    ]);

    return [$technicalAccountId, $stationId, $cameraId];
}

function createAttendanceAcademicGraph(User $operator): array
{
    $period = PeriodoAcademico::factory()->create(['creado_por' => $operator->id]);
    $grade = Grado::create([
        'periodo_academico_id' => $period->id,
        'nombre' => 'Tercero',
        'nivel' => 'Secundaria',
        'orden' => 3,
        'activo' => true,
    ]);
    $section = Seccion::create([
        'grado_id' => $grade->id,
        'nombre' => 'A',
        'turno' => 'manana',
        'activo' => true,
    ]);
    $course = Curso::factory()->create();
    $teacher = Docente::factory()->create();
    $assignment = CargaAcademica::create([
        'seccion_id' => $section->id,
        'curso_id' => $course->id,
        'docente_id' => $teacher->id,
        'vigente_desde' => '2026-03-01',
        'activo' => true,
        'asignado_por' => $operator->id,
    ]);

    return [$grade, $teacher, $assignment];
}

it('creates the biometric attendance tables required by phase two', function () {
    foreach ([
        'cuentas_tecnicas',
        'consentimientos_biometricos',
        'perfiles_faciales',
        'archivos_biometricos',
        'estaciones_biometricas',
        'camaras_estacion',
        'activaciones_estacion',
        'eventos_reconocimiento',
        'asistencias_alumnos',
        'asistencias_docentes',
        'movimientos_asistencia',
        'anomalias_asistencia',
        'configuraciones_jornada',
        'sesiones_clase',
        'tarifas_docentes',
        'liquidaciones_descuento_docentes',
    ] as $table) {
        expect(Schema::hasTable($table))->toBeTrue($table.' table should exist');
    }
});

it('rejects conflicting active biometric state and invalid biometric measurements', function () {
    $operator = biometricSchemaOperator();
    $person = User::factory()->create();

    DB::table('consentimientos_biometricos')->insert([
        'id' => (string) Str::uuid(),
        'user_id' => $person->id,
        'estado' => 'otorgado',
        'otorgado_por' => $operator->id,
        'documento_version' => 'v1',
        'otorgado_en' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn () => DB::table('consentimientos_biometricos')->insert([
        'id' => (string) Str::uuid(),
        'user_id' => $person->id,
        'estado' => 'otorgado',
        'otorgado_por' => $operator->id,
        'documento_version' => 'v1',
        'otorgado_en' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);

    expect(fn () => DB::table('perfiles_faciales')->insert([
        'id' => (string) Str::uuid(),
        'user_id' => $person->id,
        'embedding_cifrado' => random_bytes(32),
        'modelo_version' => 'face-recognition-v1',
        'calidad' => 1.5000,
        'activo' => true,
        'enrolado_por' => $operator->id,
        'enrolado_en' => now(),
        'ultima_actualizacion_en' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

it('enforces attendance relation and actor checks for movements and anomalies', function () {
    $operator = biometricSchemaOperator();
    [$technicalAccountId] = createTechnicalStation($operator);
    $student = Alumno::factory()->create();

    $attendanceId = (string) Str::uuid();
    DB::table('asistencias_alumnos')->insert([
        'id' => $attendanceId,
        'alumno_id' => $student->id,
        'fecha' => '2026-06-07',
        'estado' => 'presente',
        'presencia_abierta' => true,
        'registrado_por' => $operator->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn () => DB::table('movimientos_asistencia')->insert([
        'id' => (string) Str::uuid(),
        'tipo' => 'ingreso',
        'motivo' => 'regular',
        'ocurrido_en' => now(),
        'origen' => 'facial',
        'registrado_por' => $operator->id,
        'cuenta_tecnica_id' => $technicalAccountId,
        'created_at' => now(),
    ]))->toThrow(QueryException::class)
        ->and(fn () => DB::table('anomalias_asistencia')->insert([
            'id' => (string) Str::uuid(),
            'tipo' => 'sin_salida',
            'estado' => 'pendiente',
            'detalle' => 'Sin salida registrada',
            'asignado_a' => $operator->id,
            'created_at' => now(),
        ]))->toThrow(QueryException::class);
});

it('models expiration windows and pending recognition indexes', function () {
    $operator = biometricSchemaOperator();
    [$technicalAccountId, $stationId, $cameraId] = createTechnicalStation($operator);

    DB::table('activaciones_estacion')->insert([
        'id' => (string) Str::uuid(),
        'estacion_id' => $stationId,
        'codigo_hash' => hash('sha256', 'activation-code'),
        'expira_en' => now()->addMinutes(10),
        'creado_por' => $operator->id,
        'created_at' => now(),
    ]);

    DB::table('eventos_reconocimiento')->insert([
        'id' => (string) Str::uuid(),
        'idempotency_key' => 'capture-001',
        'estacion_id' => $stationId,
        'camara_estacion_id' => $cameraId,
        'cuenta_tecnica_id' => $technicalAccountId,
        'tipo_persona' => 'desconocido',
        'confianza' => 0.7000,
        'prueba_vida_superada' => true,
        'estado' => 'pendiente_revision',
        'capturado_en' => now(),
        'recibido_en' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(DB::table('eventos_reconocimiento')->where('estado', 'pendiente_revision')->count())->toBe(1)
        ->and(fn () => DB::table('activaciones_estacion')->insert([
            'id' => (string) Str::uuid(),
            'estacion_id' => $stationId,
            'codigo_hash' => hash('sha256', 'late-activation-code'),
            'expira_en' => now()->addMinutes(11),
            'creado_por' => $operator->id,
            'created_at' => now(),
        ]))->toThrow(QueryException::class);
});

it('publishes the partial index for pending recognition review queues', function () {
    $indexExists = DB::table('pg_indexes')
        ->where('schemaname', 'public')
        ->where('tablename', 'eventos_reconocimiento')
        ->where('indexname', 'eventos_reconocimiento_pendientes_idx')
        ->where('indexdef', 'like', "%WHERE ((estado)::text = 'pendiente_revision'::text)%")
        ->exists();

    expect($indexExists)->toBeTrue();
});

it('creates teacher attendance and payroll persistence with historical uniqueness', function () {
    $operator = biometricSchemaOperator();
    [$grade, $teacher, $assignment] = createAttendanceAcademicGraph($operator);

    DB::table('configuraciones_jornada')->insert([
        'id' => (string) Str::uuid(),
        'nombre' => 'Jornada regular',
        'grado_id' => $grade->id,
        'dia_semana' => 1,
        'hora_limite_puntual' => '07:45:00',
        'hora_cierre_asistencia' => '14:00:00',
        'activo' => true,
        'configurado_por' => $operator->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('sesiones_clase')->insert([
        'id' => (string) Str::uuid(),
        'carga_academica_id' => $assignment->id,
        'fecha' => '2026-06-08',
        'hora_inicio' => '08:00:00',
        'hora_fin' => '09:30:00',
        'estado' => 'programada',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('tarifas_docentes')->insert([
        'id' => (string) Str::uuid(),
        'docente_id' => $teacher->id,
        'tarifa_hora' => 50.00,
        'vigente_desde' => '2026-06-01',
        'registrado_por' => $operator->id,
        'created_at' => now(),
    ]);

    DB::table('liquidaciones_descuento_docentes')->insert([
        'id' => (string) Str::uuid(),
        'docente_id' => $teacher->id,
        'periodo_anio' => 2026,
        'periodo_mes' => 6,
        'tarifa_hora_snapshot' => 50.00,
        'minutos_tardanza' => 15,
        'monto_tardanza' => 12.50,
        'monto_total_descuento' => 12.50,
        'estado' => 'borrador',
        'calculado_por' => $operator->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn () => DB::table('liquidaciones_descuento_docentes')->insert([
        'id' => (string) Str::uuid(),
        'docente_id' => $teacher->id,
        'periodo_anio' => 2026,
        'periodo_mes' => 6,
        'tarifa_hora_snapshot' => 55.00,
        'monto_total_descuento' => 0,
        'estado' => 'borrador',
        'calculado_por' => $operator->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

it('rolls back all biometric attendance tables cleanly', function () {
    Artisan::call('migrate:rollback', ['--step' => 2]);

    expect(Schema::hasTable('liquidaciones_descuento_docentes'))->toBeFalse()
        ->and(Schema::hasTable('cuentas_tecnicas'))->toBeFalse();
});
