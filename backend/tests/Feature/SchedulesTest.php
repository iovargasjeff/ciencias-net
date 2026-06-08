<?php

namespace Tests\Feature;

use App\Modules\Academico\Infrastructure\Models\CargaAcademica;
use App\Modules\Academico\Infrastructure\Models\Curso;
use App\Modules\Academico\Infrastructure\Models\Grado;
use App\Modules\Academico\Infrastructure\Models\PeriodoAcademico;
use App\Modules\Academico\Infrastructure\Models\Seccion;
use App\Modules\Horarios\Infrastructure\Models\Horario;
use App\Modules\Usuarios\Infrastructure\Models\Docente;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SchedulesTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;

    private User $docenteUser;

    private CargaAcademica $carga1;

    private CargaAcademica $carga2;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'superadmin']);
        Role::create(['name' => 'docente']);

        $this->superadmin = User::factory()->create();
        $this->superadmin->assignRole('superadmin');

        $this->docenteUser = User::factory()->create();
        $this->docenteUser->assignRole('docente');

        $docente = Docente::factory()->create(['user_id' => $this->docenteUser->id]);
        $docente2 = Docente::factory()->create();

        $periodo = PeriodoAcademico::create([
            'nombre' => '2026', 'tipo' => 'colegio', 'fecha_inicio' => '2026-03-01', 'fecha_fin' => '2026-12-15', 'estado' => 'activo', 'creado_por' => $this->superadmin->id,
        ]);
        $grado = Grado::create(['periodo_academico_id' => $periodo->id, 'nombre' => '3ro', 'nivel' => 'Secundaria', 'orden' => 3]);
        $seccion1 = Seccion::create(['grado_id' => $grado->id, 'nombre' => 'A', 'turno' => 'manana']);
        $seccion2 = Seccion::create(['grado_id' => $grado->id, 'nombre' => 'B', 'turno' => 'manana']);
        $curso = Curso::create(['codigo' => 'MAT1', 'nombre' => 'Matemáticas']);

        $this->carga1 = CargaAcademica::create([
            'seccion_id' => $seccion1->id, 'curso_id' => $curso->id, 'docente_id' => $docente->id, 'vigente_desde' => '2026-03-01', 'asignado_por' => $this->superadmin->id,
        ]);

        $this->carga2 = CargaAcademica::create([
            'seccion_id' => $seccion2->id, 'curso_id' => $curso->id, 'docente_id' => $docente->id, 'vigente_desde' => '2026-03-01', 'asignado_por' => $this->superadmin->id,
        ]);
    }

    public function test_superadmin_can_create_schedule()
    {
        $response = $this->actingAs($this->superadmin)->postJson('/api/v1/schedules', [
            'teaching_assignment_id' => $this->carga1->id,
            'weekday' => 1,
            'starts_at' => '08:00',
            'ends_at' => '09:30',
            'room' => 'Aula 101',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.dia_semana', 1)
            ->assertJsonPath('data.hora_inicio', '08:00')
            ->assertJsonPath('data.hora_fin', '09:30');

        $this->assertDatabaseHas('horarios', [
            'carga_academica_id' => $this->carga1->id,
            'dia_semana' => 1,
            'aula' => 'Aula 101',
        ]);
    }

    public function test_docente_cannot_create_schedule()
    {
        $response = $this->actingAs($this->docenteUser)->postJson('/api/v1/schedules', [
            'teaching_assignment_id' => $this->carga1->id,
            'weekday' => 1,
            'starts_at' => '08:00',
            'ends_at' => '09:30',
        ]);

        $response->assertStatus(403);
    }

    public function test_cannot_create_schedule_with_overlap_same_docente()
    {
        Horario::create([
            'carga_academica_id' => $this->carga1->id,
            'dia_semana' => 1,
            'hora_inicio' => '08:00:00',
            'hora_fin' => '09:30:00',
        ]);

        $response = $this->actingAs($this->superadmin)->postJson('/api/v1/schedules', [
            'teaching_assignment_id' => $this->carga2->id, // Mismo docente, otra sección
            'weekday' => 1,
            'starts_at' => '09:00', // Cruce
            'ends_at' => '10:30',
        ]);

        $response->assertStatus(409);
    }
}
