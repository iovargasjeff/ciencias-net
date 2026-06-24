<?php

use App\Modules\Academico\Infrastructure\Models\CargaAcademica;
use App\Modules\Academico\Infrastructure\Models\Curso;
use App\Modules\Academico\Infrastructure\Models\Grado;
use App\Modules\Academico\Infrastructure\Models\PeriodoAcademico;
use App\Modules\Academico\Infrastructure\Models\Seccion;
use App\Modules\Usuarios\Infrastructure\Models\Alumno;
use App\Modules\Usuarios\Infrastructure\Models\Docente;
use App\Modules\Usuarios\Infrastructure\Models\Padre;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('lets a user manager administer operational roles but blocks sensitive and self changes', function () {
    $manager = User::factory()->create();
    $manager->assignRole('gestor_usuarios');
    $target = User::factory()->create();

    $this->actingAs($manager)
        ->putJson("/api/v1/accounts/{$target->id}/roles", ['roles' => ['docente']])
        ->assertOk()
        ->assertJsonPath('data.roles.0', 'docente');

    $this->actingAs($manager)
        ->putJson("/api/v1/accounts/{$target->id}/roles", ['roles' => ['superadmin']])
        ->assertForbidden();

    $this->actingAs($manager)
        ->putJson("/api/v1/accounts/{$manager->id}/roles", ['roles' => ['docente']])
        ->assertForbidden();

    $this->assertDatabaseHas('audit_logs', ['action' => 'account.roles_rejected']);
});

it('deactivates accounts without deleting their history', function () {
    $admin = User::factory()->create();
    $admin->assignRole('superadmin');
    $target = User::factory()->create();

    $this->actingAs($admin)
        ->putJson("/api/v1/accounts/{$target->id}/activation", ['active' => false])
        ->assertOk()
        ->assertJsonPath('data.active', false);

    $this->assertDatabaseHas('users', ['id' => $target->id, 'activo' => false]);
    $this->assertDatabaseHas('audit_logs', ['model_id' => $target->id, 'action' => 'account.activation_changed']);
});

it('supports family N to M links and blocks access to an unrelated student', function () {
    $admin = User::factory()->create();
    $admin->assignRole('gestor_usuarios');
    $parent = Padre::factory()->create();
    $parent->user->assignRole('padre');
    $first = Alumno::factory()->create();
    $second = Alumno::factory()->create();
    $unrelated = Alumno::factory()->create();

    $linkId = null;
    foreach ([$first, $second] as $student) {
        $response = $this->actingAs($admin)->postJson('/api/v1/family-links', [
            'parent_account_id' => $parent->user_id,
            'student_id' => $student->id,
            'relationship' => 'padre',
        ])->assertCreated();
        $linkId ??= $response->json('data.id');
    }

    $this->actingAs($admin)->postJson('/api/v1/family-links', [
        'parent_account_id' => $parent->user_id,
        'student_id' => $first->id,
        'relationship' => 'padre',
    ])->assertConflict()->assertJsonPath('error.code', 'conflict');

    $this->actingAs($parent->user)->getJson('/api/v1/family/students')
        ->assertOk()->assertJsonCount(2, 'data');

    $this->actingAs($parent->user)->getJson("/api/v1/family/students/{$unrelated->id}/summary")
        ->assertForbidden();

    $this->actingAs($admin)->deleteJson("/api/v1/family-links/{$linkId}")->assertNoContent();
    $this->assertDatabaseHas('audit_logs', ['action' => 'family_link.removed', 'model' => 'family_link', 'model_id' => $linkId]);
});

it('preserves historical teaching assignments when a teacher changes', function () {
    $coordinator = User::factory()->create();
    $coordinator->assignRole('coordinador_academico');
    $period = PeriodoAcademico::factory()->create(['creado_por' => $coordinator->id]);
    $grade = Grado::create([
        'periodo_academico_id' => $period->id, 'nombre' => 'Primero', 'nivel' => 'primaria', 'orden' => 1, 'activo' => true,
    ]);
    $section = Seccion::create([
        'grado_id' => $grade->id, 'nombre' => 'A', 'turno' => 'manana', 'capacidad' => 30, 'activo' => true,
    ]);
    $course = Curso::factory()->create();
    $oldTeacher = Docente::factory()->create();
    $newTeacher = Docente::factory()->create();
    $old = CargaAcademica::create([
        'seccion_id' => $section->id, 'curso_id' => $course->id, 'docente_id' => $oldTeacher->id,
        'vigente_desde' => now()->subMonth(), 'activo' => true, 'asignado_por' => $coordinator->id,
    ]);

    $this->actingAs($coordinator)->postJson('/api/v1/teaching-assignments', [
        'teacher_id' => $newTeacher->id, 'course_id' => $course->id,
        'grade_id' => $grade->id, 'section_id' => $section->id, 'academic_period_id' => $period->id,
    ])->assertCreated()->assertJsonPath('data.teacher_id', $newTeacher->id);

    expect($old->fresh()->activo)->toBeFalse()
        ->and($old->fresh()->vigente_hasta)->not->toBeNull()
        ->and(CargaAcademica::count())->toBe(2);
});

it('prevents teachers from editing academic structure', function () {
    $teacher = User::factory()->create();
    $teacher->assignRole('docente');

    $this->actingAs($teacher)->postJson('/api/v1/courses', [
        'code' => 'MAT-99', 'name' => 'Matemática',
    ])->assertForbidden();
});

it('rejects invalid academic validity ranges', function () {
    $coordinator = User::factory()->create();
    $coordinator->assignRole('coordinador_academico');

    $this->actingAs($coordinator)->postJson('/api/v1/academic-periods', [
        'name' => 'Periodo inválido', 'start_date' => '2026-12-01', 'end_date' => '2026-03-01',
    ])->assertUnprocessable()->assertJsonPath('error.code', 'validation_failed');
});
