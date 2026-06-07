<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollLiquidationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'teacher_id' => $this->docente_id,
            'period_year' => $this->periodo_anio,
            'period_month' => $this->periodo_mes,
            'hourly_rate_snapshot' => (string) $this->tarifa_hora_snapshot,
            'late_minutes' => $this->minutos_tardanza,
            'justified_absence_hours' => (string) $this->horas_falta_justificada,
            'unjustified_absence_hours' => (string) $this->horas_falta_injustificada,
            'late_amount' => (string) $this->monto_tardanza,
            'justified_absence_amount' => (string) $this->monto_falta_justificada,
            'unjustified_absence_amount' => (string) $this->monto_falta_injustificada,
            'adjustment_amount' => (string) $this->monto_ajuste,
            'total_discount_amount' => (string) $this->monto_total_descuento,
            'status' => $this->estado,
            'calculated_by' => $this->calculado_por,
            'closed_by' => $this->cerrada_por,
            'closed_at' => $this->cerrada_en?->toISOString(),
        ];
    }
}
