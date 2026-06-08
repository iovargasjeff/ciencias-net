<?php

namespace Tests\Feature;

use App\Modules\Academico\Infrastructure\Models\PeriodoAcademico;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CalendarEventsTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'superadmin']);

        $this->superadmin = User::factory()->create();
        $this->superadmin->assignRole('superadmin');

        PeriodoAcademico::create([
            'nombre' => '2026', 'tipo' => 'colegio', 'fecha_inicio' => '2026-03-01', 'fecha_fin' => '2026-12-15', 'estado' => 'activo', 'creado_por' => $this->superadmin->id
        ]);
    }

    public function test_superadmin_can_create_calendar_event()
    {
        $this->withoutExceptionHandling();
        $response = $this->actingAs($this->superadmin)->postJson('/api/v1/calendar-events', [
            'title' => 'Reunión de Padres',
            'starts_at' => '2026-05-15 10:00:00',
            'ends_at' => '2026-05-15 12:00:00',
            'event_type' => 'meeting',
            'description' => 'Reunión obligatoria'
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('data.titulo', 'Reunión de Padres')
                 ->assertJsonPath('data.tipo', 'evento'); // Mapeado de meeting a evento

        $this->assertDatabaseHas('eventos_calendario', [
            'titulo' => 'Reunión de Padres',
            'tipo' => 'evento',
            'descripcion' => 'Reunión obligatoria'
        ]);
    }

    public function test_can_create_holiday_event()
    {
        $response = $this->actingAs($this->superadmin)->postJson('/api/v1/calendar-events', [
            'title' => 'Feriado Nacional',
            'starts_at' => '2026-07-28 00:00:00',
            'ends_at' => '2026-07-28 23:59:59',
            'event_type' => 'holiday',
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('data.tipo', 'no_laboral');

        $this->assertDatabaseHas('eventos_calendario', [
            'titulo' => 'Feriado Nacional',
            'tipo' => 'no_laboral',
        ]);
    }
}
