<?php

namespace App\Modules\Asistencia\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecognitionEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'station_id' => $this->estacion_id,
            'camera_id' => $this->camara_estacion_id,
            'user_id' => $this->user_id,
            'person_type' => $this->tipo_persona,
            'resolved_event_type' => $this->tipo_evento_resuelto,
            'confidence' => (float) $this->confianza,
            'liveness_passed' => $this->prueba_vida_superada,
            'status' => $this->estado,
            'status_reason' => $this->motivo_estado,
            'captured_at' => $this->capturado_en?->toISOString(),
            'reviewed_by' => $this->revisado_por,
            'reviewed_at' => $this->revisado_en?->toISOString(),
        ];
    }
}
