<?php

namespace App\Modules\Finanzas\Domain\Services;

use App\Modules\Finanzas\Application\DTOs\CreatePaymentMovementDTO;
use App\Modules\Finanzas\Domain\Repositories\PaymentMovementRepositoryInterface;
use App\Modules\Finanzas\Infrastructure\Models\MovimientoPago;
use App\Modules\Finanzas\Infrastructure\Models\ObligacionPago;
use App\Modules\Usuarios\Infrastructure\Models\User;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class PaymentMovementService
{
    public function __construct(
        private PaymentMovementRepositoryInterface $repository,
        private AuditLogger $auditLogger
    ) {}

    public function createPayment(
        ObligacionPago $obligation,
        CreatePaymentMovementDTO $dto,
        User $registeredBy
    ): MovimientoPago {
        return DB::transaction(function () use ($obligation, $dto, $registeredBy) {
            if ($obligation->estado !== 'pendiente') {
                throw new ConflictHttpException(
                    "Solo se pueden pagar obligaciones en estado 'pendiente'. Estado actual: {$obligation->estado}"
                );
            }

            if ($this->repository->hasPaymentForObligation($obligation->id)) {
                throw new ConflictHttpException('Esta obligación ya tiene un pago registrado.');
            }

            $applicableAmount = $obligation->getApplicableAmount($dto->occurredAt);

            if (abs((float) $dto->amount - $applicableAmount) > 0.01) {
                throw new ConflictHttpException(
                    "El monto del pago debe ser exactamente S/ {$applicableAmount}. Monto recibido: S/ {$dto->amount}."
                );
            }

            $movement = new MovimientoPago([
                'obligacion_pago_id' => $obligation->id,
                'tipo' => 'pago',
                'monto' => $dto->amount,
                'medio_pago' => $this->mapMethod($dto->method),
                'referencia' => $dto->reference,
                'numero_recibo' => $this->repository->nextReceiptNumber(),
                'motivo' => null,
                'registrado_por' => $registeredBy->id,
            ]);

            $this->repository->save($movement);

            $obligation->update([
                'estado' => 'pagado',
                'monto_cobrado' => $dto->amount,
                'fecha_pago' => $dto->occurredAt,
            ]);

            $this->auditLogger->record(
                null,
                'finance.payment_registered',
                $registeredBy,
                $obligation,
                newValues: [
                    'movement_id' => $movement->id,
                    'obligation_id' => $obligation->id,
                    'amount' => $dto->amount,
                    'method' => $dto->method,
                    'receipt_number' => $movement->numero_recibo,
                ]
            );

            return $movement;
        });
    }

    public function createReversal(
        MovimientoPago $originalPayment,
        string $reason,
        User $registeredBy
    ): MovimientoPago {
        return DB::transaction(function () use ($originalPayment, $reason, $registeredBy) {
            if ($originalPayment->tipo !== 'pago') {
                throw new ConflictHttpException('Solo se puede anular un movimiento de tipo pago.');
            }

            $reversal = new MovimientoPago([
                'obligacion_pago_id' => $originalPayment->obligacion_pago_id,
                'tipo' => 'anulacion',
                'monto' => $originalPayment->monto,
                'medio_pago' => null,
                'referencia' => null,
                'numero_recibo' => $this->repository->nextReceiptNumber(),
                'motivo' => $reason,
                'registrado_por' => $registeredBy->id,
            ]);

            $this->repository->save($reversal);

            $obligation = $originalPayment->obligacionPago;
            $obligation->update([
                'estado' => 'pendiente',
                'monto_cobrado' => null,
                'fecha_pago' => null,
            ]);

            $this->auditLogger->record(
                null,
                'finance.payment_reversed',
                $registeredBy,
                $obligation,
                newValues: [
                    'reversal_id' => $reversal->id,
                    'original_payment_id' => $originalPayment->id,
                    'reason' => $reason,
                ]
            );

            return $reversal;
        });
    }

    public function createRefund(
        MovimientoPago $originalPayment,
        float $refundAmount,
        string $reason,
        User $registeredBy
    ): MovimientoPago {
        return DB::transaction(function () use ($originalPayment, $refundAmount, $reason, $registeredBy) {
            if ($originalPayment->tipo !== 'pago') {
                throw new ConflictHttpException('Solo se puede devolver un movimiento de tipo pago.');
            }

            if ($refundAmount > $originalPayment->monto) {
                throw new ConflictHttpException(
                    "El monto de devolución no puede exceder el monto del pago original ({$originalPayment->monto})."
                );
            }

            $refund = new MovimientoPago([
                'obligacion_pago_id' => $originalPayment->obligacion_pago_id,
                'tipo' => 'devolucion',
                'monto' => $refundAmount,
                'medio_pago' => null,
                'referencia' => null,
                'numero_recibo' => $this->repository->nextReceiptNumber(),
                'motivo' => $reason,
                'registrado_por' => $registeredBy->id,
            ]);

            $this->repository->save($refund);

            $this->auditLogger->record(
                null,
                'finance.payment_refunded',
                $registeredBy,
                $originalPayment,
                newValues: [
                    'refund_id' => $refund->id,
                    'original_payment_id' => $originalPayment->id,
                    'refund_amount' => $refundAmount,
                    'reason' => $reason,
                ]
            );

            return $refund;
        });
    }

    private function mapMethod(string $apiMethod): string
    {
        return match ($apiMethod) {
            'cash' => 'efectivo',
            'transfer' => 'transferencia',
            'yape' => 'yape',
            'plin' => 'plin',
            'card', 'other' => 'otro',
            default => $apiMethod,
        };
    }
}
