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

class AcademicReportsTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;

    private User $docente;

    private User $alumno;

    private User $padre;

    private Examen $examen;

    private Matricula $matricula1;

    private Matricula $matricula2;

    private Matricula $matricula3;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'superadmin']);
        Role::create(['name' => 'docente']);
        Role::create(['name' => 'alumno']);
        Role::create(['name' => 'padre']);

        $this->superadmin = User::factory()->create();
        $this->superadmin->assignRole('superadmin');

        $this->docente = User::factory()->create();
        $this->docente->assignRole('docente');

        $this->alumno = User::factory()->create();
        $this->alumno->assignRole('alumno');

        $this->padre = User::factory()->create();
        $this->padre->assignRole('padre');

        $periodo = PeriodoAcademico::create([
            'nombre' => '2026', 'tipo' => 'regular', 'estado' => 'activo', 'fecha_inicio' => now(), 'fecha_fin' => now()->addMonths(10),
            'creado_por' => $this->superadmin->id,
        ]);

        $grado = Grado::create(['periodo_academico_id' => $periodo->id, 'nombre' => '1ro Secundaria', 'nivel' => 'secundaria', 'orden' => 1]);
        $seccion = Seccion::create(['grado_id' => $grado->id, 'nombre' => 'A', 'turno' => 'mañana']);
        $curso = Curso::create(['codigo' => 'MAT1', 'nombre' => 'Matemáticas', 'area' => 'ciencias', 'grado_id' => $grado->id]);

        $docenteId = Str::uuid();
        DB::table('docentes')->insert(['id' => $docenteId, 'user_id' => $this->docente->id, 'dni' => '12345678', 'nombres' => 'D', 'apellidos' => '1']);

        $carga = CargaAcademica::create([
            'periodo_academico_id' => $periodo->id,
            'curso_id' => $curso->id,
            'seccion_id' => $seccion->id,
            'docente_id' => $docenteId,
            'asignado_por' => $this->superadmin->id,
            'vigente_desde' => now(),
        ]);

        $this->examen = Examen::create([
            'carga_academica_id' => $carga->id,
            'titulo' => 'Examen Parcial',
            'assessment_type' => 'exam',
            'fecha_aplicacion' => now()->subDay(),
            'periodo_nombre' => '2026',
            'canal' => 'general',
            'total_preguntas' => 20,
            'puntaje_maximo' => 20,
            'estado' => 'borrador',
        ]);

        $alumno1Id = Str::uuid();
        DB::table('alumnos')->insert(['id' => $alumno1Id, 'user_id' => User::factory()->create()->id, 'dni' => '111', 'nombres' => 'A', 'apellidos' => '1']);

        $alumno2Id = Str::uuid();
        DB::table('alumnos')->insert(['id' => $alumno2Id, 'user_id' => User::factory()->create()->id, 'dni' => '222', 'nombres' => 'A', 'apellidos' => '2']);

        $alumno3Id = Str::uuid();
        DB::table('alumnos')->insert(['id' => $alumno3Id, 'user_id' => User::factory()->create()->id, 'dni' => '333', 'nombres' => 'A', 'apellidos' => '3']);

        $this->matricula1 = Matricula::create([
            'periodo_academico_id' => $periodo->id, 'seccion_id' => $seccion->id, 'alumno_id' => $alumno1Id,
            'codigo' => 'MAT001', 'fecha' => now(), 'registrado_por' => $this->superadmin->id, 'estado' => 'activa',
        ]);
        $this->matricula2 = Matricula::create([
            'periodo_academico_id' => $periodo->id, 'seccion_id' => $seccion->id, 'alumno_id' => $alumno2Id,
            'codigo' => 'MAT002', 'fecha' => now(), 'registrado_por' => $this->superadmin->id, 'estado' => 'activa',
        ]);
        $this->matricula3 = Matricula::create([
            'periodo_academico_id' => $periodo->id, 'seccion_id' => $seccion->id, 'alumno_id' => $alumno3Id,
            'codigo' => 'MAT003', 'fecha' => now(), 'registrado_por' => $this->superadmin->id, 'estado' => 'activa',
        ]);
    }

    public function test_publish_assessment_calculates_ranking()
    {
        Nota::create([
            'examen_id' => $this->examen->id,
            'matricula_id' => $this->matricula1->id,
            'puntaje' => 18,
            'estado' => 'registrada',
            'registrado_por' => $this->docente->id,
        ]);

        Nota::create([
            'examen_id' => $this->examen->id,
            'matricula_id' => $this->matricula2->id,
            'puntaje' => 20,
            'estado' => 'registrada',
            'registrado_por' => $this->docente->id,
        ]);

        Nota::create([
            'examen_id' => $this->examen->id,
            'matricula_id' => $this->matricula3->id,
            'puntaje' => null,
            'estado' => 'ausente',
            'registrado_por' => $this->docente->id,
        ]);

        $response = $this->actingAs($this->superadmin)
            ->postJson("/api/v1/assessments/{$this->examen->id}/publication");

        $response->assertStatus(200);

        $this->assertDatabaseHas('examenes', [
            'id' => $this->examen->id,
            'estado' => 'publicado',
        ]);

        $this->assertDatabaseHas('notas', [
            'matricula_id' => $this->matricula2->id,
            'puesto_ranking' => 1,
        ]);

        $this->assertDatabaseHas('notas', [
            'matricula_id' => $this->matricula1->id,
            'puesto_ranking' => 2,
        ]);

        $this->assertDatabaseHas('notas', [
            'matricula_id' => $this->matricula3->id,
            'puesto_ranking' => null,
        ]);
    }

    public function test_list_rankings()
    {
        $response = $this->actingAs($this->alumno)
            ->getJson('/api/v1/rankings');

        $response->assertStatus(200);
    }

    public function test_generate_report()
    {
        $response = $this->actingAs($this->alumno)
            ->postJson('/api/v1/academic-reports', [
                'format' => 'pdf',
                'report_type' => 'report_card',
            ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_correct_result()
    {
        $this->examen->update(['estado' => 'publicado']);

        $nota = Nota::create([
            'examen_id' => $this->examen->id,
            'matricula_id' => $this->matricula1->id,
            'puntaje' => 15,
            'estado' => 'registrada',
            'registrado_por' => $this->docente->id,
        ]);

        $response = $this->actingAs($this->superadmin)
            ->postJson("/api/v1/assessment-results/{$nota->id}/corrections", [
                'score' => 20,
                'reason' => 'Error de digitación',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('notas', [
            'id' => $nota->id,
            'puntaje' => 20,
        ]);
    }
}
