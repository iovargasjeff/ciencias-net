<?php

namespace App\Modules\Finanzas\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentConceptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->codigo,
            'name' => $this->nombre,
            'academic_period_id' => $this->periodo_academico_id,
            'type' => $this->tipo,
            'year' => $this->periodo_anio,
            'month' => $this->periodo_mes,
            'amount' => $this->monto_base,
            'early_payment_discount' => $this->descuento_pronto_pago,
            'early_payment_deadline' => $this->fecha_limite_pronto_pago?->toDateString(),
            'status' => $this->estado,
            'valid_from' => $this->vigente_desde?->toDateString(),
            'valid_until' => $this->vigente_hasta?->toDateString(),
            'replaces_concept_id' => $this->reemplaza_concepto_id,
        ];
    }
}
