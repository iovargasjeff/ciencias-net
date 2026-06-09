<?php

namespace App\Modules\Incidencias\Application\UseCases;

use App\Modules\Incidencias\Domain\Mappers\IncidentMapper;
use App\Modules\Incidencias\Infrastructure\Models\HistorialIncidencia;
use App\Modules\Incidencias\Infrastructure\Models\Incidencia;
use Illuminate\Support\Facades\DB;

class TransitionIncident
{
    public function execute(Incidencia $incidencia, array $data, string $userId): void
    {
        DB::transaction(function () use ($incidencia, $data, $userId) {
            $newDbStatus = IncidentMapper::statusToDb($data['target_status']);
            $oldDbStatus = $incidencia->estado;

            if ($newDbStatus !== $oldDbStatus) {
                // Actualizar estado
                $incidencia->update(['estado' => $newDbStatus]);

                // Ajustar asignado_a si cambia a derivado
                if ($newDbStatus === 'derivado_toe') {
                    $incidencia->update(['asignado_a' => 'toe']);
                } elseif ($newDbStatus === 'derivado_psicologia') {
                    $incidencia->update(['asignado_a' => 'psicologia']);
                }

                // Registrar en el historial
                HistorialIncidencia::create([
                    'incidencia_id' => $incidencia->id,
                    'accion' => "Cambio de estado: {$data['target_status']}",
                    'detalle' => $data['reason'],
                    'registrado_por' => $userId,
                ]);
            }
        });
    }
}
