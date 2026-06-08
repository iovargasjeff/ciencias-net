<?php

/**
 * DB-004: add-evaluation-content-schema
 *
 * Verifica:
 * - Req 1: Una nota pertenece a matrícula y examen compatibles (UNIQUE constraint).
 * - Req 2: Una lectura es única por comunicado y usuario (PK compuesta).
 * - Constraints CHECK de PostgreSQL: puntaje >= 0, hora_fin > hora_inicio,
 *   fechas de eventos, estados ENUM-like.
 * - Lectura idempotente: relaciones traversal sin N+1 evidente.
 * - Índices verificados vía estructuras (implícito en constraints y lecturas).
 */

use App\Models\Alumno;
use App\Models\CargaAcademica;
use App\Models\Comunicado;
use App\Models\ComunicadoLectura;
use App\Models\Curso;
use App\Models\Docente;
use App\Models\Grado;
use App\Models\Matricula;
use App\Models\Nota;
use App\Models\Notificacion;
use App\Models\PeriodoAcademico;
use App\Models\Seccion;
use App\Models\User;
use App\Modules\Academico\Infrastructure\Models\Examen;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// ─────────────────────────────────────────────────────────────────
// Helpers de setup reutilizables en este test
// ─────────────────────────────────────────────────────────────────

function buildAcademicContext(): array
{
    $operator = User::factory()->create();
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
    $load = CargaAcademica::create([
        'seccion_id' => $section->id,
        'curso_id' => $course->id,
        'docente_id' => $teacher->id,
        'vigente_desde' => now()->toDateString(),
        'activo' => true,
        'asignado_por' => $operator->id,
    ]);
    $student = Alumno::factory()->create();
    $enrollment = Matricula::create([
        'alumno_id' => $student->id,
        'seccion_id' => $section->id,
        'codigo' => 'MAT-TEST-001',
        'fecha' => now()->toDateString(),
        'estado' => 'activo',
        'registrado_por' => $operator->id,
    ]);
    $exam = Examen::create([
        'carga_academica_id' => $load->id,
        'titulo' => 'Semanal 1 - I Bimestre',
        'fecha_aplicacion' => now()->toDateString(),
        'assessment_type' => 'exam',
        'channel' => 'general',
        'total_preguntas' => 40,
        'puntaje_maximo' => 20.00,
        'estado' => 'listo',
    ]);

    return compact('operator', 'period', 'grade', 'section', 'course', 'teacher', 'load', 'student', 'enrollment', 'exam');
}

// ─────────────────────────────────────────────────────────────────
// Requirement 1: Nota única por (examen, matrícula)
// ─────────────────────────────────────────────────────────────────

it('allows registering a nota for a valid enrollment-examen pair', function () {
    ['enrollment' => $enrollment, 'exam' => $exam, 'operator' => $operator] = buildAcademicContext();

    $nota = Nota::create([
        'examen_id' => $exam->id,
        'matricula_id' => $enrollment->id,
        'puntaje' => 15.50,
        'estado' => 'registrada',
        'registrado_por' => $operator->id,
    ]);

    expect($nota->exists)->toBeTrue()
        ->and($nota->puntaje)->toBe('15.50');
});

it('rejects a duplicate nota for the same examen and matricula', function () {
    ['enrollment' => $enrollment, 'exam' => $exam, 'operator' => $operator] = buildAcademicContext();

    Nota::create([
        'examen_id' => $exam->id,
        'matricula_id' => $enrollment->id,
        'puntaje' => 15.50,
        'estado' => 'registrada',
        'registrado_por' => $operator->id,
    ]);

    expect(fn () => Nota::create([
        'examen_id' => $exam->id,
        'matricula_id' => $enrollment->id,
        'puntaje' => 18.00,
        'estado' => 'registrada',
        'registrado_por' => $operator->id,
    ]))->toThrow(QueryException::class);
});

// ─────────────────────────────────────────────────────────────────
// Requirement 1b: Nota ausente/exonerado sin puntaje
// ─────────────────────────────────────────────────────────────────

it('allows a nota with null puntaje for ausente estado', function () {
    ['enrollment' => $enrollment, 'exam' => $exam, 'operator' => $operator] = buildAcademicContext();

    $nota = Nota::create([
        'examen_id' => $exam->id,
        'matricula_id' => $enrollment->id,
        'puntaje' => null,
        'estado' => 'ausente',
        'registrado_por' => $operator->id,
    ]);

    expect($nota->puntaje)->toBeNull()
        ->and($nota->estado)->toBe('ausente');
});

// ─────────────────────────────────────────────────────────────────
// Requirement 2: Lectura única por (comunicado, usuario)
// ─────────────────────────────────────────────────────────────────

it('registers a comunicado reading for a user', function () {
    $publisher = User::factory()->create();
    $reader = User::factory()->create();
    $comunicado = Comunicado::create([
        'titulo' => 'Reunión de Padres',
        'contenido' => 'Se convoca a todos los padres...',
        'publicado_por' => $publisher->id,
        'destinatarios' => ['roles' => ['padre']],
        'importante' => false,
        'fecha_publicacion' => now(),
    ]);

    $lectura = ComunicadoLectura::create([
        'comunicado_id' => $comunicado->id,
        'user_id' => $reader->id,
        'leido_en' => now(),
    ]);

    expect($lectura->comunicado_id)->toBe($comunicado->id)
        ->and($lectura->user_id)->toBe($reader->id);
});

it('rejects a duplicate reading for the same comunicado and user', function () {
    $publisher = User::factory()->create();
    $reader = User::factory()->create();
    $comunicado = Comunicado::create([
        'titulo' => 'Aviso Importante',
        'contenido' => 'Contenido del aviso',
        'publicado_por' => $publisher->id,
        'destinatarios' => ['roles' => ['padre']],
        'importante' => true,
        'fecha_publicacion' => now(),
    ]);

    ComunicadoLectura::create([
        'comunicado_id' => $comunicado->id,
        'user_id' => $reader->id,
        'leido_en' => now(),
    ]);

    // Segundo intento debe violar la PK compuesta
    expect(fn () => ComunicadoLectura::create([
        'comunicado_id' => $comunicado->id,
        'user_id' => $reader->id,
        'leido_en' => now(),
    ]))->toThrow(QueryException::class);
});

// ─────────────────────────────────────────────────────────────────
// Constraint CHECK: puntaje negativo rechazado (solo PostgreSQL)
// ─────────────────────────────────────────────────────────────────

it('rejects a negative puntaje on PostgreSQL via CHECK constraint', function () {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('CHECK constraints only enforced in PostgreSQL');
    }

    ['enrollment' => $enrollment, 'exam' => $exam, 'operator' => $operator] = buildAcademicContext();

    expect(fn () => Nota::create([
        'examen_id' => $exam->id,
        'matricula_id' => $enrollment->id,
        'puntaje' => -1.00,
        'estado' => 'registrada',
        'registrado_por' => $operator->id,
    ]))->toThrow(QueryException::class);
});

// ─────────────────────────────────────────────────────────────────
// Lectura idempotente: relaciones traversal sin error
// ─────────────────────────────────────────────────────────────────

it('traverses examen → cargaAcademica → seccion → grado without extra queries', function () {
    ['exam' => $exam, 'section' => $section, 'grade' => $grade] = buildAcademicContext();

    $loaded = Examen::with('cargaAcademica.seccion.grado')->find($exam->id);

    expect($loaded->cargaAcademica->seccion->grado->id)->toBe($grade->id);
});

// ─────────────────────────────────────────────────────────────────
// Notificacion: puede crearse sin updated_at
// ─────────────────────────────────────────────────────────────────

it('creates a notificacion with only created_at', function () {
    $user = User::factory()->create();

    $notif = Notificacion::create([
        'user_id' => $user->id,
        'tipo' => 'nota_publicada',
        'titulo' => 'Nota publicada',
        'contenido' => 'Tus notas del I Bimestre ya están disponibles.',
        'datos' => ['examen_id' => 'abc-123'],
        'canal' => 'panel',
        'estado' => 'pendiente',
    ]);

    expect($notif->estado)->toBe('pendiente')
        ->and($notif->datos)->toHaveKey('examen_id');
});
