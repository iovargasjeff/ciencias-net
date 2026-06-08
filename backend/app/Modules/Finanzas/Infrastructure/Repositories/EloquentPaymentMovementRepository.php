<?php

namespace App\Modules\Finanzas\Infrastructure\Repositories;

use App\Modules\Finanzas\Domain\Repositories\PaymentMovementRepositoryInterface;
use App\Modules\Finanzas\Infrastructure\Models\MovimientoPago;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentPaymentMovementRepository implements PaymentMovementRepositoryInterface
{
    public function findOrFail(string $id): MovimientoPago
    {
        return MovimientoPago::with('obligacionPago', 'registradoPor')
            ->findOrFail($id);
    }

    public function byObligation(string $obligationId): Collection
    {
        return MovimientoPago::query()
            ->where('obligacion_pago_id', $obligationId)
            ->latest()
            ->get();
    }

    public function hasPaymentForObligation(string $obligationId): bool
    {
        return MovimientoPago::query()
            ->where('obligacion_pago_id', $obligationId)
            ->where('tipo', 'pago')
            ->exists();
    }

    public function nextReceiptNumber(): string
    {
        $year = now()->format('Y');

        $last = MovimientoPago::query()
            ->where('numero_recibo', 'like', "REC-{$year}-%")
            ->orderBy('numero_recibo', 'desc')
            ->lockForUpdate()
            ->first();

        if ($last) {
            $parts = explode('-', $last->numero_recibo);
            $seq = ((int) end($parts)) + 1;
        } else {
            $seq = 1;
        }

        return sprintf('REC-%s-%05d', $year, $seq);
    }

    public function save(MovimientoPago $movement): MovimientoPago
    {
        $movement->save();

        return $movement;
    }
}
