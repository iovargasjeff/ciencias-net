<?php

namespace App\Modules\Finanzas\Domain\Repositories;

use App\Modules\Finanzas\Infrastructure\Models\MovimientoPago;
use Illuminate\Support\Collection;

interface PaymentMovementRepositoryInterface
{
    public function findOrFail(string $id): MovimientoPago;

    public function byObligation(string $obligationId): Collection;

    public function hasPaymentForObligation(string $obligationId): bool;

    public function nextReceiptNumber(): string;

    public function save(MovimientoPago $movement): MovimientoPago;
}
