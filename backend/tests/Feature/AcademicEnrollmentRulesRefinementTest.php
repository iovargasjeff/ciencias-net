<?php

namespace Tests\Feature;

use App\Modules\Academico\Infrastructure\Models\CargaAcademica;
use App\Modules\Academico\Infrastructure\Models\Curso;
use App\Modules\Academico\Infrastructure\Models\Grado;
use App\Modules\Academico\Infrastructure\Models\PeriodoAcademico;
use App\Modules\Academico\Infrastructure\Models\Seccion;
use App\Modules\Horarios\Infrastructure\Models\Horario;
use App\Modules\Usuarios\Infrastructure\Models\Alumno;
use App\Modules\Usuarios\Infrastructure\Models\Docente;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicEnrollmentRulesRefinementTest extends TestCase
{
    use RefreshDatabase;

    private User $coordinator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->coordinator = User::factory()->create();
        $this->coordinator->assignRole('coordinador_academico');
    }

    public function test_period_terms_reject_overlaps_and_store_valid_terms(): void
    {
        $payload = [
            'name' => 'Año académico 2026',
            'start_date' => '2026-03-01',
            'end_date' => '2026-12-20',
            'terms' => [
                ['name' => 'I Bimestre', 'start_date' => '2026-03-01', 'end_date' => '2026-05-01'],
                ['name' => 'II Bimestre', 'start_date' => '2026-04-25', 'end_date' => '2026-07-01'],
                ['name' => 'III Bimestre', 'start_date' => '2026-07-02', 'end_date' => '2026-09-15'],
                ['name' => 'IV Bimestre', 'start_date' => '2026-09-16', 'end_date' => '2026-12-20'],
            ],
        ];

        $this->actingAs($this->coordinator)->postJson('/api/v1/academic-periods', $payload)
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed');

        $payload['terms'][1] = ['name' => 'II Bimestre', 'start_date' => '2026-05-02', 'end_date' => '2026-07-01'];

        $this->actingAs($this->coordinator)->postJson('/api/v1/academic-periods', $payload)
            ->assertCreated()
            ->assertJsonCount(4, 'data.terms');
    }

    public function test_grade_catalog_and_course_uniqueness_by_grade(): void
    {
        $period = $this->period();

        $this->actingAs($this->coordinator)->getJson('/api/v1/grade-catalog')
            ->assertOk()
            ->assertJsonPath('data.4.code', '5-secundaria');

        $first = $this->actingAs($this->coordinator)->postJson('/api/v1/grades', [
            'academic_period_id' => $period->id,
            'catalog_code' => '5-secundaria',
        ])->assertCreated();

        $this->actingAs($this->coordinator)->postJson('/api/v1/grades', [
            'academic_period_id' => $period->id,
            'name' => 'Grado libre inventado',
        ])->assertUnprocessable();

        $gradeId = $first->json('data.id');
        $this->actingAs($this->coordinator)->postJson('/api/v1/courses', [
            'grade_id' => $gradeId,
            'code' => 'MAT-5S',
            'name' => 'Matemática',
        ])->assertCreated();

        $this->actingAs($this->coordinator)->postJson('/api/v1/courses', [
            'grade_id' => $gradeId,
            'code' => 'MAT-5S-B',
            'name' => ' matematica ',
        ])->assertConflict();
    }

    public function test_enrollment_capacity_filters_search_and_derived_schedule(): void
    {
        [$period, $grade, $section, $course, $teacher, $student] = $this->academicContext(sectionCapacity: 1);
        $secondStudent = Alumno::factory()->create(['dni' => '76543210', 'nombres' => 'Luis', 'apellidos' => 'Capacidad']);

        $this->actingAs($this->coordinator)->postJson('/api/v1/enrollments', [
            'student_id' => $student->id,
            'grade_id' => $grade->id,
            'section_id' => $section->id,
            'academic_period_id' => $period->id,
        ])->assertCreated();

        $this->actingAs($this->coordinator)->postJson('/api/v1/enrollments', [
            'student_id' => $secondStudent->id,
            'grade_id' => $grade->id,
            'section_id' => $section->id,
            'academic_period_id' => $period->id,
        ])->assertUnprocessable()->assertJsonPath('error.fields.section_id.0', 'La sección no tiene cupos disponibles.');

        $this->actingAs($this->coordinator)->getJson("/api/v1/sections?grade_id={$grade->id}&with_available_seats=1")
            ->assertOk()
            ->assertJsonPath('data.0.id', $section->id);

        $this->actingAs($this->coordinator)->getJson('/api/v1/search/students?search=Ana')
            ->assertOk()
            ->assertJsonPath('data.0.id', $student->id);

        $assignment = CargaAcademica::create([
            'seccion_id' => $section->id,
            'curso_id' => $course->id,
            'docente_id' => $teacher->id,
            'vigente_desde' => '2026-03-01',
            'activo' => true,
            'asignado_por' => $this->coordinator->id,
        ]);
        Horario::create([
            'carga_academica_id' => $assignment->id,
            'dia_semana' => 1,
            'hora_inicio' => '08:00',
            'hora_fin' => '09:00',
        ]);

        $this->actingAs($this->coordinator)->getJson("/api/v1/schedules?student_id={$student->id}")
            ->assertOk()
            ->assertJsonPath('data.0.carga_academica_id', $assignment->id);
    }

    public function test_assessment_requires_matching_grade_section_and_course(): void
    {
        [$period, $grade, $section, $course, $teacher] = $this->academicContext();
        $assignment = CargaAcademica::create([
            'seccion_id' => $section->id,
            'curso_id' => $course->id,
            'docente_id' => $teacher->id,
            'vigente_desde' => '2026-03-01',
            'activo' => true,
            'asignado_por' => $this->coordinator->id,
        ]);

        $payload = [
            'grade_id' => $grade->id,
            'section_id' => $section->id,
            'course_id' => $course->id,
            'teaching_assignment_id' => $assignment->id,
            'title' => 'Semanal 1',
            'assessment_type' => 'exam',
            'max_score' => '20.00',
            'assessment_date' => '2026-04-10',
            'channel' => 'general',
            'total_questions' => 40,
        ];

        $this->actingAs($this->coordinator)->postJson('/api/v1/assessments', array_merge($payload, [
            'course_id' => Curso::factory()->create()->id,
        ]))->assertUnprocessable()->assertJsonPath('error.fields.course_id.0', 'El curso no corresponde a la carga académica seleccionada.');

        $this->actingAs($this->coordinator)->postJson('/api/v1/assessments', $payload)
            ->assertCreated();
    }

    private function period(): PeriodoAcademico
    {
        return PeriodoAcademico::factory()->create(['creado_por' => $this->coordinator->id]);
    }

    private function academicContext(int $sectionCapacity = 30): array
    {
        $period = $this->period();
        $grade = Grado::create([
            'periodo_academico_id' => $period->id,
            'catalog_code' => '5-secundaria',
            'nombre' => 'Quinto de secundaria',
            'nivel' => 'secundaria',
            'orden' => 5,
            'activo' => true,
        ]);
        $section = Seccion::create(['grado_id' => $grade->id, 'nombre' => 'A', 'turno' => 'manana', 'capacidad' => $sectionCapacity]);
        $course = Curso::create([
            'grado_id' => $grade->id,
            'codigo' => 'MAT-'.$sectionCapacity,
            'nombre' => 'Matemática',
            'nombre_normalizado' => 'matematica',
            'activo' => true,
        ]);
        $suffix = str_pad((string) $sectionCapacity, 2, '0', STR_PAD_LEFT);
        $teacher = Docente::factory()->create(['dni' => '123456'.$suffix]);
        $student = Alumno::factory()->create(['dni' => '223456'.$suffix, 'nombres' => 'Ana', 'apellidos' => 'Prueba']);

        return [$period, $grade, $section, $course, $teacher, $student];
    }
}
