<?php

namespace Tests\Feature;

use App\Modules\Incidencias\Infrastructure\Models\HistorialIncidencia;
use App\Modules\Incidencias\Infrastructure\Models\Incidencia;
use App\Modules\Psicologia\Infrastructure\Models\AtencionPsicologica;
use App\Modules\Usuarios\Infrastructure\Models\Alumno;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncidentsDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_incident_and_history()
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $alumno = Alumno::factory()->create();
        $auxiliar = User::factory()->create();
        $auxiliar->assignRole('auxiliar');

        $incidencia = Incidencia::create([
            'alumno_id' => $alumno->id,
            'reportado_por' => $auxiliar->id,
            'fecha' => now(),
            'tipo' => 'conducta',
            'severidad' => 'leve',
            'descripcion' => 'Interrupción constante',
            'asignado_a' => 'auxiliar',
            'estado' => 'abierto',
        ]);

        $this->assertDatabaseHas('incidencias', [
            'id' => $incidencia->id,
            'estado' => 'abierto',
        ]);

        $historial = HistorialIncidencia::create([
            'incidencia_id' => $incidencia->id,
            'accion' => 'Creación',
            'detalle' => 'Incidencia reportada por auxiliar',
            'registrado_por' => $auxiliar->id,
        ]);

        $this->assertDatabaseHas('historial_incidencias', [
            'id' => $historial->id,
        ]);

        // Verificar que rechaza un enum inválido
        $this->expectException(QueryException::class);
        Incidencia::create([
            'alumno_id' => $alumno->id,
            'reportado_por' => $auxiliar->id,
            'fecha' => now(),
            'tipo' => 'tipo_invalido_inexistente',
            'severidad' => 'leve',
            'descripcion' => 'Interrupción',
            'asignado_a' => 'auxiliar',
            'estado' => 'abierto',
        ]);
    }

    public function test_can_create_psychology_attention()
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $alumno = Alumno::factory()->create();
        $psicologa = User::factory()->create();
        $psicologa->assignRole('psicologia');

        $atencion = AtencionPsicologica::create([
            'incidencia_id' => null, // Puede ser null
            'alumno_id' => $alumno->id,
            'psicologa_id' => $psicologa->id,
            'fecha_atencion' => now(),
            'notas_privadas' => 'Notas encriptadas o privadas del paciente.',
        ]);

        $this->assertDatabaseHas('atenciones_psicologia', [
            'id' => $atencion->id,
        ]);
    }
}
