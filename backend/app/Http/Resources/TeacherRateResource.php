<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherRateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'teacher_id' => $this->docente_id,
            'hourly_rate' => (string) $this->tarifa_hora,
            'effective_from' => $this->vigente_desde?->toDateString(),
            'effective_until' => $this->vigente_hasta?->toDateString(),
            'registered_by' => $this->registrado_por,
        ];
    }
}
