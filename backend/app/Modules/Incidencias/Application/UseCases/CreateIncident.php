<?php

namespace App\Modules\Incidencias\Application\UseCases;

use App\Modules\Incidencias\Domain\Mappers\IncidentMapper;
use App\Modules\Incidencias\Infrastructure\Models\HistorialIncidencia;
use App\Modules\Incidencias\Infrastructure\Models\Incidencia;
use Illuminate\Support\Facades\DB;

class CreateIncident
{
    public function execute(array $data, string $userId): Incidencia
    {
        return DB::transaction(function () use ($data, $userId) {
            $incidencia = Incidencia::create([
                'alumno_id' => $data['student_id'],
                'reportado_por' => $userId,
                'fecha' => $data['occurred_at'],
                'tipo' => $data['incident_type'],
                'severidad' => IncidentMapper::severityToDb($data['severity']),
                'descripcion' => $data['description'],
                'asignado_a' => 'auxiliar', // Por defecto inicia con auxiliar
                'estado' => 'abierto',
            ]);

            HistorialIncidencia::create([
                'incidencia_id' => $incidencia->id,
                'accion' => 'Creación',
                'detalle' => 'Incidencia registrada en el sistema.',
                'registrado_por' => $userId,
            ]);

            return $incidencia;
        });
    }
}
