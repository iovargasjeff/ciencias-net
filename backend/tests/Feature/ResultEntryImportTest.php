<?php

namespace Tests\Feature;

use App\Modules\Academico\Infrastructure\Models\CargaAcademica;
use App\Modules\Academico\Infrastructure\Models\Curso;
use App\Modules\Academico\Infrastructure\Models\Examen;
use App\Modules\Academico\Infrastructure\Models\Grado;
use App\Modules\Academico\Infrastructure\Models\Matricula;
use App\Modules\Academico\Infrastructure\Models\Nota;
use App\Modules\Academico\Infrastructure\Models\PeriodoAcademico;
use App\Modules\Academico\Infrastructure\Models\Seccion;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ResultEntryImportTest extends TestCase
{
    use RefreshDatabase;

    private User $docente;

    private User $otroDocente;

    private User $coordinador;

    private Examen $examen;

    private Matricula $matricula1;

    private Matricula $matricula2;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'docente']);
        Role::create(['name' => 'coordinador_academico']);
        Role::create(['name' => 'alumno']);

        $this->docente = User::factory()->create();
        $this->docente->assignRole('docente');
        $docenteId = Str::uuid();
        DB::table('docentes')->insert(['id' => $docenteId, 'user_id' => $this->docente->id, 'dni' => '12345678', 'nombres' => 'D', 'apellidos' => '1']);

        $this->otroDocente = User::factory()->create();
        $this->otroDocente->assignRole('docente');
        $otroDocenteId = Str::uuid();
        DB::table('docentes')->insert(['id' => $otroDocenteId, 'user_id' => $this->otroDocente->id, 'dni' => '87654321', 'nombres' => 'D', 'apellidos' => '2']);

        $this->coordinador = User::factory()->create();
        $this->coordinador->assignRole('coordinador_academico');

        $periodo = PeriodoAcademico::create([
            'nombre' => '2026', 'tipo' => 'regular', 'estado' => 'activo', 'fecha_inicio' => now(), 'fecha_fin' => now()->addMonths(10),
            'creado_por' => $this->coordinador->id,
        ]);

        $grado = Grado::create(['periodo_academico_id' => $periodo->id, 'nombre' => '1ro Secundaria', 'nivel' => 'secundaria', 'orden' => 1]);
        $seccion = Seccion::create(['grado_id' => $grado->id, 'nombre' => 'A', 'turno' => 'mañana']);
        $curso = Curso::create(['codigo' => 'MAT1', 'nombre' => 'Matemáticas', 'area' => 'ciencias', 'grado_id' => $grado->id]);

        $carga = CargaAcademica::create([
            'periodo_academico_id' => $periodo->id,
            'curso_id' => $curso->id,
            'seccion_id' => $seccion->id,
            'docente_id' => $docenteId,
            'asignado_por' => $this->coordinador->id,
            'vigente_desde' => now(),
        ]);

        $alumno1 = User::factory()->create();
        $alumno1->assignRole('alumno');
        $alumno1Id = Str::uuid();
        DB::table('alumnos')->insert(['id' => $alumno1Id, 'user_id' => $alumno1->id, 'dni' => '11111111', 'nombres' => 'A', 'apellidos' => '1']);

        $alumno2 = User::factory()->create();
        $alumno2->assignRole('alumno');
        $alumno2Id = Str::uuid();
        DB::table('alumnos')->insert(['id' => $alumno2Id, 'user_id' => $alumno2->id, 'dni' => '22222222', 'nombres' => 'A', 'apellidos' => '2']);

        $this->matricula1 = Matricula::create([
            'periodo_academico_id' => $periodo->id, 'seccion_id' => $seccion->id, 'alumno_id' => $alumno1Id,
            'codigo' => 'MAT001', 'fecha' => now(),
            'registrado_por' => $this->coordinador->id, 'estado' => 'activa',
        ]);

        $this->matricula2 = Matricula::create([
            'periodo_academico_id' => $periodo->id, 'seccion_id' => $seccion->id, 'alumno_id' => $alumno2Id,
            'codigo' => 'MAT002', 'fecha' => now(),
            'registrado_por' => $this->coordinador->id, 'estado' => 'activa',
        ]);

        $this->examen = Examen::create([
            'carga_academica_id' => $carga->id,
            'titulo' => 'Examen Parcial',
            'assessment_type' => 'parcial',
            'fecha_aplicacion' => now()->subDay(),
            'periodo_nombre' => '2026',
            'total_preguntas' => 20,
            'puntaje_maximo' => 20,
            'estado' => 'listo',
        ]);
    }

    public function test_docente_puede_registrar_nota_valida(): void
    {
        $response = $this->actingAs($this->docente)->postJson("/api/v1/assessments/{$this->examen->id}/grades", [
            'matricula_id' => $this->matricula1->id,
            'estado' => 'registrada',
            'puntaje' => 15.5,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('notas', [
            'examen_id' => $this->examen->id,
            'matricula_id' => $this->matricula1->id,
            'puntaje' => 15.5,
        ]);
    }

    public function test_carga_ajena_esta_bloqueada(): void
    {
        $response = $this->actingAs($this->otroDocente)->postJson("/api/v1/assessments/{$this->examen->id}/grades", [
            'matricula_id' => $this->matricula1->id,
            'estado' => 'registrada',
            'puntaje' => 15.5,
        ]);

        $response->assertStatus(403);
    }

    public function test_importacion_masiva_guarda_notas_validas(): void
    {
        $response = $this->actingAs($this->docente)->postJson("/api/v1/assessments/{$this->examen->id}/grades/import", [
            'notas' => [
                ['matricula_id' => $this->matricula1->id, 'estado' => 'registrada', 'puntaje' => 18],
                ['matricula_id' => $this->matricula2->id, 'estado' => 'ausente'],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('notas', ['matricula_id' => $this->matricula1->id, 'puntaje' => 18]);
        $this->assertDatabaseHas('notas', ['matricula_id' => $this->matricula2->id, 'estado' => 'ausente', 'puntaje' => null]);
    }

    public function test_importacion_invalida_revierte_todo(): void
    {
        $response = $this->actingAs($this->docente)->postJson("/api/v1/assessments/{$this->examen->id}/grades/import", [
            'notas' => [
                ['matricula_id' => $this->matricula1->id, 'estado' => 'registrada', 'puntaje' => 18],
                ['matricula_id' => $this->matricula2->id, 'estado' => 'registrada', 'puntaje' => 25], // Mayor a 20!
            ],
        ]);

        $response->assertStatus(422);

        // Verifica que la nota 1 tampoco se guardó por el rollback
        $this->assertDatabaseMissing('notas', ['matricula_id' => $this->matricula1->id]);
    }

    public function test_actualizar_nota_registra_auditoria(): void
    {
        $nota = Nota::create([
            'examen_id' => $this->examen->id,
            'matricula_id' => $this->matricula1->id,
            'puntaje' => 12,
            'estado' => 'registrada',
            'registrado_por' => $this->docente->id,
        ]);

        $response = $this->actingAs($this->docente)->putJson("/api/v1/grades/{$nota->id}", [
            'matricula_id' => $this->matricula1->id, // request rule requires it
            'estado' => 'registrada',
            'puntaje' => 16, // Cambio
        ]);

        $response->assertStatus(200);

        // Verifica la tabla de auditoría
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->docente->id,
            'model' => 'Nota',
            'model_id' => $nota->id,
            'action' => 'UPDATE_NOTA',
        ]);

        $log = DB::table('audit_logs')->where('model_id', $nota->id)->first();
        $oldValues = json_decode($log->old_values, true);
        $newValues = json_decode($log->new_values, true);

        $this->assertEquals(12, $oldValues['puntaje']);
        $this->assertEquals(16, $newValues['puntaje']);
    }
}
