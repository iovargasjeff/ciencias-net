<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentAttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->alumno_id,
            'date' => $this->fecha?->toDateString(),
            'first_entry' => $this->primer_ingreso,
            'last_exit' => $this->ultima_salida,
            'status' => $this->estado,
            'open_presence' => $this->presencia_abierta,
            'movements' => StudentAttendanceMovementResource::collection($this->whenLoaded('movimientos')),
        ];
    }
}
