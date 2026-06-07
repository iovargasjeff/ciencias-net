<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StationCameraResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'station_id' => $this->estacion_id,
            'label' => $this->nombre,
            'device_identifier' => $this->device_id_navegador,
            'mode' => match ($this->modo) {
                'entrada' => 'entry',
                'salida' => 'exit',
                default => 'mixed',
            },
            'active' => $this->activo,
        ];
    }
}
