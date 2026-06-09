<?php

use App\Modules\Academico\Infrastructure\Models\Grado;
use App\Modules\Academico\Infrastructure\Models\Matricula;
use App\Modules\Academico\Infrastructure\Models\PeriodoAcademico;
use App\Modules\Academico\Infrastructure\Models\Seccion;
use App\Modules\Usuarios\Infrastructure\Models\Alumno;
use App\Modules\Usuarios\Infrastructure\Models\Padre;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('rejects duplicate human emails', function () {
    User::factory()->create(['email' => 'familia@example.test']);

    expect(fn () => User::factory()->create(['email' => 'familia@example.test']))
        ->toThrow(QueryException::class);
});

it('supports many-to-many family links without duplicate links', function () {
    $student = Alumno::factory()->create();
    $firstParent = Padre::factory()->create();
    $secondParent = Padre::factory()->create();

    $student->padres()->attach($firstParent, ['relacion' => 'Madre', 'es_contacto_principal' => true]);
    $student->padres()->attach($secondParent, ['relacion' => 'Padre']);

    expect($student->padres()->count())->toBe(2)
        ->and(fn () => $student->padres()->attach($firstParent, ['relacion' => 'Madre']))
        ->toThrow(QueryException::class);
});

it('links an enrollment to a student section and academic period', function () {
    $operator = User::factory()->create();
    $period = PeriodoAcademico::factory()->create(['creado_por' => $operator->id]);
    $grade = Grado::create([
        'periodo_academico_id' => $period->id,
        'nombre' => 'Primero',
        'nivel' => 'Secundaria',
        'orden' => 1,
        'activo' => true,
    ]);
    $section = Seccion::create([
        'grado_id' => $grade->id,
        'nombre' => 'A',
        'turno' => 'manana',
        'activo' => true,
    ]);
    $student = Alumno::factory()->create();

    $enrollment = Matricula::create([
        'alumno_id' => $student->id,
        'seccion_id' => $section->id,
        'codigo' => 'MAT-2026-0001',
        'fecha' => '2026-03-01',
        'estado' => 'activo',
        'registrado_por' => $operator->id,
    ]);

    expect($enrollment->seccion->grado->periodoAcademico->is($period))->toBeTrue()
        ->and($enrollment->alumno->is($student))->toBeTrue();
});
