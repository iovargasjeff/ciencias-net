<?php

namespace Tests\Feature;

use App\Modules\Incidencias\Infrastructure\Models\Incidencia;
use App\Modules\Usuarios\Infrastructure\Models\Alumno;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncidentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_auxiliar_can_create_incident()
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $auxiliar = User::factory()->create();
        $auxiliar->assignRole('auxiliar');

        $alumno = Alumno::factory()->create();

        $response = $this->actingAs($auxiliar)->postJson('/api/v1/incidents', [
            'student_id' => $alumno->id,
            'incident_type' => 'conducta',
            'severity' => 'low',
            'description' => 'Falta leve en el aula',
            'occurred_at' => now()->toIso8601String(),
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('incidencias', [
            'alumno_id' => $alumno->id,
            'severidad' => 'leve', // Se debe haber mapeado
        ]);
        $this->assertDatabaseHas('historial_incidencias', [
            'accion' => 'Creación',
        ]);
    }

    public function test_parent_cannot_view_incidents()
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $padre = User::factory()->create();
        $padre->assignRole('padre');

        $response = $this->actingAs($padre)->getJson('/api/v1/incidents');

        $response->assertStatus(403);
    }

    public function test_toe_can_transition_and_add_follow_up()
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $toe = User::factory()->create();
        $toe->assignRole('toe');
        $alumno = Alumno::factory()->create();

        $incidencia = Incidencia::create([
            'alumno_id' => $alumno->id,
            'reportado_por' => $toe->id,
            'fecha' => now(),
            'tipo' => 'conducta',
            'severidad' => 'leve',
            'descripcion' => 'Prueba',
            'asignado_a' => 'auxiliar',
            'estado' => 'abierto',
        ]);

        $response = $this->actingAs($toe)->postJson("/api/v1/incidents/{$incidencia->id}/transitions", [
            'target_status' => 'referred_toe',
            'reason' => 'Se eleva a TOE',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('incidencias', [
            'id' => $incidencia->id,
            'estado' => 'derivado_toe',
            'asignado_a' => 'toe', // Se actualiza la asignación
        ]);

        $response2 = $this->actingAs($toe)->postJson("/api/v1/incidents/{$incidencia->id}/follow-ups", [
            'note' => 'Cita programada con el estudiante',
        ]);

        $response2->assertStatus(201);
        $this->assertDatabaseHas('historial_incidencias', [
            'incidencia_id' => $incidencia->id,
            'accion' => 'Seguimiento',
        ]);
    }
}
