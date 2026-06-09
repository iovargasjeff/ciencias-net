<?php

namespace App\Modules\Finanzas\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentBenefitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->alumno_id,
            'benefit_type' => match ($this->modalidad) {
                'porcentaje' => 'percentage',
                'monto_fijo' => 'fixed',
                default => 'waiver',
            },
            'value' => $this->valor,
            'concept_ids' => $this->whenLoaded('conceptos', fn () => $this->conceptos->pluck('id')->values()),
            'stackable_with_early_payment' => $this->acumulable_pronto_pago,
            'starts_on' => $this->vigente_desde?->toDateString(),
            'ends_on' => $this->vigente_hasta?->toDateString(),
            'active' => $this->activo,
            'reason' => $this->motivo,
        ];
    }
}
