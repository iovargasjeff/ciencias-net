<?php

namespace App\Modules\Asistencia\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentAttendanceAnomalyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'attendance_id' => $this->asistencia_alumno_id,
            'type' => $this->tipo,
            'status' => $this->estado,
            'detail' => $this->detalle,
            'assigned_to' => $this->asignado_a,
            'resolved_by' => $this->resuelto_por,
            'resolution' => $this->resolucion,
            'resolved_at' => $this->resuelto_en?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
