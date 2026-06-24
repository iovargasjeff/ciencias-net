<?php

namespace Tests\Feature\Assessments;

use App\Modules\Academico\Application\UseCases\CloseAssessment;
use App\Modules\Academico\Infrastructure\Models\CargaAcademica;
use App\Modules\Academico\Infrastructure\Models\Curso;
use App\Modules\Academico\Infrastructure\Models\Examen;
use App\Modules\Academico\Infrastructure\Models\Grado;
use App\Modules\Academico\Infrastructure\Models\PeriodoAcademico;
use App\Modules\Academico\Infrastructure\Models\Seccion;
use App\Modules\Usuarios\Infrastructure\Models\Docente;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AssessmentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'docente']);
        Role::create(['name' => 'coordinador_academico']);
        Role::create(['name' => 'superadmin']);
    }

    private function createCargaAcademica(?Docente $docente = null): CargaAcademica
    {
        $periodo = PeriodoAcademico::factory()->create();
        $grado = Grado::firstOrCreate([
            'nombre' => '1ro Secundaria',
            'nivel' => 'secundaria',
            'periodo_academico_id' => $periodo->id,
            'orden' => 1,
        ]);
        $seccion = Seccion::create(['grado_id' => $grado->id, 'periodo_academico_id' => $periodo->id, 'letra' => 'A', 'nombre' => '1ro A', 'vacantes' => 30, 'turno' => 'mañana']);
        $curso = Curso::factory()->create();
        if (! $docente) {
            $docente = Docente::factory()->create(['user_id' => User::factory()->create()->id]);
        }

        return CargaAcademica::create([
            'seccion_id' => $seccion->id,
            'curso_id' => $curso->id,
            'docente_id' => $docente->id,
            'vigente_desde' => now(),
            'asignado_por' => $docente->user_id,
        ]);
    }

    public function test_docente_cannot_create_assessment_outside_their_carga_academica(): void
    {
        $docenteUser = User::factory()->create();
        $docenteUser->assignRole('docente');
        Docente::factory()->create(['user_id' => $docenteUser->id]);

        $otherCarga = $this->createCargaAcademica(); // Belongs to another docente

        $payload = [
            'grade_id' => $otherCarga->seccion->grado_id,
            'section_id' => $otherCarga->seccion_id,
            'course_id' => $otherCarga->curso_id,
            'teaching_assignment_id' => $otherCarga->id,
            'title' => 'Examen Parcial',
            'assessment_type' => 'exam',
            'max_score' => '20.00',
            'assessment_date' => '2026-06-15',
            'channel' => 'general',
            'total_questions' => 40,
        ];

        $response = $this->actingAs($docenteUser)->postJson('/api/v1/assessments', $payload);

        $response->assertStatus(403);
    }

    public function test_docente_cannot_create_assessment_inside_their_carga_academica(): void
    {
        $docenteUser = User::factory()->create();
        $docenteUser->assignRole('docente');
        $docente = Docente::factory()->create(['user_id' => $docenteUser->id]);

        $carga = $this->createCargaAcademica($docente);

        $payload = [
            'grade_id' => $carga->seccion->grado_id,
            'section_id' => $carga->seccion_id,
            'course_id' => $carga->curso_id,
            'teaching_assignment_id' => $carga->id,
            'title' => 'Examen Parcial',
            'assessment_type' => 'exam',
            'max_score' => '20.00',
            'assessment_date' => '2026-06-15',
            'channel' => 'general',
            'total_questions' => 40,
        ];

        $response = $this->actingAs($docenteUser)->postJson('/api/v1/assessments', $payload);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('examenes', [
            'carga_academica_id' => $carga->id,
            'titulo' => 'Examen Parcial',
        ]);
    }

    public function test_coordinador_can_create_assessment_anywhere(): void
    {
        $coordinador = User::factory()->create();
        $coordinador->assignRole('coordinador_academico');

        $carga = $this->createCargaAcademica();

        $payload = [
            'grade_id' => $carga->seccion->grado_id,
            'section_id' => $carga->seccion_id,
            'course_id' => $carga->curso_id,
            'teaching_assignment_id' => $carga->id,
            'title' => 'Simulacro',
            'assessment_type' => 'practice',
            'max_score' => '100.00',
            'assessment_date' => '2026-06-16',
            'channel' => 'sciences',
            'total_questions' => 60,
        ];

        $response = $this->actingAs($coordinador)->postJson('/api/v1/assessments', $payload);

        $response->assertStatus(201);
    }

    public function test_closing_an_assessment_updates_status_and_logs(): void
    {
        $carga = $this->createCargaAcademica();
        $examen = Examen::create([
            'estado' => 'publicado',
            'carga_academica_id' => $carga->id,
            'titulo' => 'Test',
            'fecha_aplicacion' => now(),
            'assessment_type' => 'exam',
            'channel' => 'general',
            'total_preguntas' => 40,
            'puntaje_maximo' => 20,
        ]);
        $user = User::factory()->create();

        $useCase = new CloseAssessment;
        $useCase->execute($examen, $user->id);

        $this->assertEquals('cerrado', $examen->fresh()->estado);
    }
}
