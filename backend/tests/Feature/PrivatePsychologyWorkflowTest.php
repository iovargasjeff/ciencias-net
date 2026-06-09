<?php

namespace Tests\Feature;

use App\Modules\Usuarios\Infrastructure\Models\Alumno;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivatePsychologyWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_psychology_can_create_and_view_cares()
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $psicologa = User::factory()->create();
        $psicologa->assignRole('psicologia');

        $alumno = Alumno::factory()->create();

        $response = $this->actingAs($psicologa)->postJson('/api/v1/psychology-cares', [
            'student_id' => $alumno->id,
            'occurred_at' => now()->toIso8601String(),
            'summary' => 'Resumen público de la sesión',
            'confidential_notes' => 'Notas privadas sobre la sesión',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('atenciones_psicologia', [
            'alumno_id' => $alumno->id,
            'summary' => 'Resumen público de la sesión',
        ]);

        $response2 = $this->actingAs($psicologa)->getJson('/api/v1/psychology-cares');
        $response2->assertStatus(200);
        $this->assertCount(1, $response2->json('data'));
    }

    public function test_unauthorized_roles_cannot_access_psychology_cares()
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $auxiliar = User::factory()->create();
        $auxiliar->assignRole('auxiliar');

        $toe = User::factory()->create();
        $toe->assignRole('toe');

        $responseAux = $this->actingAs($auxiliar)->getJson('/api/v1/psychology-cares');
        $responseAux->assertStatus(403);

        $responseToe = $this->actingAs($toe)->getJson('/api/v1/psychology-cares');
        $responseToe->assertStatus(403);
    }
}
