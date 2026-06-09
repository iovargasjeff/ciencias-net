<?php

namespace App\Modules\Finanzas\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'obligation_id' => $this->obligacion_pago_id,
            'movement_type' => $this->tipo,
            'amount' => (float) $this->monto,
            'method' => $this->when($this->tipo === 'pago', fn () => $this->mapMethod($this->medio_pago)),
            'reference' => $this->referencia,
            'receipt_number' => $this->numero_recibo,
            'receipt_url' => $this->when(
                $this->comprobante_ruta,
                url("/api/v1/payment-movements/{$this->id}/receipt")
            ),
            'reason' => $this->motivo,
            'registered_by' => [
                'id' => $this->registradoPor?->id,
                'email' => $this->registradoPor?->email,
            ],
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }

    private function mapMethod(string $dbMethod): string
    {
        return match ($dbMethod) {
            'efectivo' => 'cash',
            'transferencia' => 'transfer',
            'yape' => 'yape',
            'plin' => 'plin',
            default => 'other',
        };
    }
}
