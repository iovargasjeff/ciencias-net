<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentAttendanceMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'attendance_id' => $this->asistencia_alumno_id,
            'type' => match ($this->tipo) {
                'ingreso' => 'entry',
                'salida' => 'exit',
                default => 're_entry',
            },
            'reason' => $this->motivo,
            'occurred_at' => $this->ocurrido_en?->toISOString(),
            'origin' => $this->origen,
            'confidence' => $this->confianza_reconocimiento === null ? null : (float) $this->confianza_reconocimiento,
            'notification_sent' => $this->notificacion_enviada,
        ];
    }
}
