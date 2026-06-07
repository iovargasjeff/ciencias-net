<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BiometricProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'student_id' => $this->whenLoaded('user', fn () => $this->user?->alumno?->id),
            'model_version' => $this->modelo_version,
            'quality' => (float) $this->calidad,
            'active' => $this->activo,
            'enrolled_by' => $this->enrolado_por,
            'enrolled_at' => $this->enrolado_en?->toISOString(),
            'updated_at' => $this->ultima_actualizacion_en?->toISOString(),
        ];
    }
}
