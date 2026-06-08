<?php

namespace Tests\Feature;

use App\Modules\Academico\Infrastructure\Models\PeriodoAcademico;
use App\Modules\Comunicados\Infrastructure\Models\Comunicado;
use App\Modules\Comunicados\Infrastructure\Models\ComunicadoLectura;
use App\Modules\Notificaciones\Application\Jobs\DistributeAnnouncementNotifications;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CommunicationsTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;
    private User $student;
    private User $parent;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->superadmin = User::factory()->create();
        $this->superadmin->assignRole('superadmin');

        $this->student = User::factory()->create();
        $this->student->assignRole('alumno');

        $this->parent = User::factory()->create();
        $this->parent->assignRole('padre');
    }

    public function test_superadmin_can_create_announcement_and_dispatch_job()
    {
        Queue::fake();

        $response = $this->actingAs($this->superadmin)->postJson('/api/v1/announcements', [
            'title' => 'Reunion de padres',
            'body' => 'El viernes tendremos reunion general',
            'audience_type' => 'roles',
            'audience_ids' => ['padre']
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('comunicados', [
            'titulo' => 'Reunion de padres',
        ]);

        Queue::assertPushed(DistributeAnnouncementNotifications::class);
    }

    public function test_mark_read_is_idempotent()
    {
        $comunicado = Comunicado::create([
            'titulo' => 'Aviso',
            'contenido' => 'Body',
            'destinatarios' => ['all' => []],
            'publicado_por' => $this->superadmin->id,
            'fecha_publicacion' => now(),
            'importante' => false,
        ]);

        // First read
        $response1 = $this->actingAs($this->parent)->putJson("/api/v1/announcements/{$comunicado->id}/read");
        $response1->assertStatus(204);

        $this->assertDatabaseHas('comunicado_lecturas', [
            'comunicado_id' => $comunicado->id,
            'user_id' => $this->parent->id,
        ]);

        // Second read - idempotent
        $response2 = $this->actingAs($this->parent)->putJson("/api/v1/announcements/{$comunicado->id}/read");
        $response2->assertStatus(204);

        $this->assertEquals(1, ComunicadoLectura::where('comunicado_id', $comunicado->id)
            ->where('user_id', $this->parent->id)->count());
    }

    public function test_user_only_sees_own_announcements_by_section_scenario()
    {
        // GIVEN se publica para otro grado
        // En nuestro caso usaremos segments
        $comunicado1 = Comunicado::create([
            'titulo' => 'Aviso General',
            'contenido' => 'Para todos',
            'destinatarios' => ['all' => []],
            'publicado_por' => $this->superadmin->id,
            'fecha_publicacion' => now(),
            'importante' => false,
        ]);

        // Mock job executing to create notification for student
        $job = new DistributeAnnouncementNotifications($comunicado1);
        $job->handle();

        $comunicado2 = Comunicado::create([
            'titulo' => 'Aviso Seccion Especifica',
            'contenido' => 'Solo otra seccion',
            'destinatarios' => ['sections' => ['99999999-9999-9999-9999-999999999999']],
            'publicado_por' => $this->superadmin->id,
            'fecha_publicacion' => now(),
            'importante' => false,
        ]);
        
        $job2 = new DistributeAnnouncementNotifications($comunicado2);
        $job2->handle();

        // WHEN usuario consulta
        $this->withoutExceptionHandling();
        $response = $this->actingAs($this->student)->getJson('/api/v1/announcements');

        // THEN no aparece el de otra seccion
        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertCount(1, $data);
        $this->assertEquals('Aviso General', $data[0]['titulo']);
    }
}
