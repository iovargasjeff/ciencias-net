<?php

namespace App\Modules\Finanzas\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource for payment obligation.
 */
class PaymentObligationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student' => [
                'id' => $this->alumno->id,
                'name' => $this->alumno->nombres.' '.$this->alumno->apellidos,
                'email' => $this->alumno->user?->email,
            ],
            'concept' => [
                'id' => $this->concepto->id,
                'name' => $this->concepto->nombre,
                'type' => $this->concepto->tipo,
            ],
            'status' => $this->estado,
            'amounts' => [
                'base' => (float) $this->monto_base_snapshot,
                'benefit_deduction' => (float) $this->monto_beneficio_snapshot,
                'ordinary' => (float) $this->monto_ordinario_snapshot,
                'early_payment' => (float) $this->monto_pronto_pago_snapshot,
                'early_payment_discount' => (float) $this->descuento_pronto_pago_aplicado,
                'paid' => $this->monto_cobrado ? (float) $this->monto_cobrado : null,
            ],
            'dates' => [
                'early_payment_deadline' => $this->fecha_limite_pronto_pago_snapshot?->toDateString(),
                'due_date' => $this->fecha_vencimiento?->toDateString(),
                'paid_at' => $this->fecha_pago?->toIso8601String(),
            ],
            'benefit' => $this->beneficio ? [
                'id' => $this->beneficio->id,
                'type' => $this->beneficio->tipo,
                'modality' => $this->beneficio->modalidad,
            ] : null,
            'audit' => [
                'created_by' => $this->registradoPor?->email,
                'created_at' => $this->created_at->toIso8601String(),
                'updated_by' => $this->actualizadoFinanzasPor?->email,
                'last_modification_reason' => $this->motivo_ultima_modificacion,
                'updated_at' => $this->updated_at->toIso8601String(),
            ],
        ];
    }
}
