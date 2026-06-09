<?php

namespace App\Modules\Incidencias\Application\UseCases;

use App\Modules\Incidencias\Infrastructure\Models\HistorialIncidencia;
use App\Modules\Incidencias\Infrastructure\Models\Incidencia;

class AddIncidentFollowUp
{
    public function execute(Incidencia $incidencia, array $data, string $userId): void
    {
        $archivoRuta = null;
        if (isset($data['file'])) {
            $archivoRuta = 'uploads/incidents/'.uniqid().'.pdf'; // Simulado temporalmente
        }

        HistorialIncidencia::create([
            'incidencia_id' => $incidencia->id,
            'accion' => 'Seguimiento',
            'detalle' => $data['note'],
            'archivo_ruta' => $archivoRuta,
            'registrado_por' => $userId,
        ]);
    }
}
