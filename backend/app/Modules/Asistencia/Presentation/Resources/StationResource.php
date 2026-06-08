<?php

namespace App\Modules\Asistencia\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->codigo,
            'name' => $this->nombre,
            'location' => $this->ubicacion,
            'mode' => $this->configuracion['mode'] ?? 'mixed',
            'active' => $this->activo,
            'activated_at' => $this->activado_en?->toISOString(),
            'revoked_at' => $this->revocado_en?->toISOString(),
            'last_seen_at' => $this->ultimo_contacto?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
