<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherAttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'teacher_id' => $this->docente_id,
            'date' => $this->fecha?->toDateString(),
            'first_entry' => $this->primer_ingreso,
            'last_exit' => $this->ultima_salida,
            'status' => $this->estado,
            'late_minutes' => $this->minutos_tardanza,
            'substitute_teacher_id' => $this->docente_sustituto_id,
        ];
    }
}
