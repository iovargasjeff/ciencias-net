<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'academic_assignment_id' => $this->carga_academica_id,
            'date' => $this->fecha?->toDateString(),
            'starts_at' => $this->hora_inicio,
            'ends_at' => $this->hora_fin,
            'status' => $this->estado,
            'cancellation_reason' => $this->motivo_cancelacion,
            'cancelled_by' => $this->cancelada_por,
            'substitute_teacher_id' => $this->docente_sustituto_id,
            'payroll_reviewed_by' => $this->revisado_planilla_por,
        ];
    }
}
