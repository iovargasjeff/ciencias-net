<?php

namespace App\Modules\Finanzas\Domain\Services;

use App\Modules\Finanzas\Infrastructure\Models\ObligacionPago;
use App\Modules\Usuarios\Infrastructure\Models\User;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Service for adjusting pending payment obligations.
 *
 * Handles:
 * - Validating obligation can be modified (must be pending)
 * - Updating allowed fields (amounts, dates, benefit)
 * - Recording audit trail with before/after values
 * - Dispatching notification events
 */
class ObligationAdjustmentService
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * Adjust a pending payment obligation.
     *
     * @param ObligacionPago $obligation
     * @param array $adjustmentData Keys: adjustment_type, amount, reason
     * @param User $adjustedBy
     *
     * @throws ConflictHttpException If obligation is not pending
     */
    public function adjust(
        ObligacionPago $obligation,
        array $adjustmentData,
        User $adjustedBy
    ): ObligacionPago {
        // Validate obligation can be adjusted
        if ($obligation->estado !== 'pendiente') {
            throw new ConflictHttpException(
                "Solo se pueden ajustar obligaciones en estado 'pendiente'. Estado actual: {$obligation->estado}"
            );
        }

        return DB::transaction(function () use ($obligation, $adjustmentData, $adjustedBy) {
            $old = $obligation->toArray();

            // Apply adjustment based on type
            $updatedFields = $this->calculateAdjustment(
                $obligation,
                $adjustmentData['adjustment_type'],
                (float) $adjustmentData['amount']
            );

            // Update obligation
            $obligation->update(array_merge($updatedFields, [
                'actualizado_finanzas_por' => $adjustedBy->id,
                'motivo_ultima_modificacion' => $adjustmentData['reason'],
            ]));

            $obligation->refresh();

            // Record audit trail
            $this->auditLogger->record(
                null,
                'finance.obligation_adjusted',
                $adjustedBy,
                $obligation,
                $old,
                $obligation->toArray()
            );

            // Dispatch notification event
            // (listeners will handle email/panel notification)
            Event::dispatch('obligation.adjusted', [
                'obligation' => $obligation,
                'adjustedBy' => $adjustedBy,
                'reason' => $adjustmentData['reason'],
            ]);

            return $obligation;
        });
    }

    /**
     * Adjust multiple obligations based on filters.
     *
     * @param array $filters Keys: obligation_ids[], concept_id, grade_id, section_id
     * @param array $adjustmentData Keys: adjustment_type, amount, reason
     * @param User $adjustedBy
     *
     * @return int Number of obligations adjusted
     */
    public function bulkAdjust(
        array $filters,
        array $adjustmentData,
        User $adjustedBy
    ): int {
        return DB::transaction(function () use ($filters, $adjustmentData, $adjustedBy) {
            $query = ObligacionPago::query()
                ->where('estado', 'pendiente');

            // Apply filters
            if (! empty($filters['obligation_ids'])) {
                $query->whereIn('id', $filters['obligation_ids']);
            }

            if ($filters['concept_id'] ?? null) {
                $query->where('concepto_id', $filters['concept_id']);
            }

            if ($filters['grade_id'] ?? null) {
                $query->whereHas('alumno.matriculas', function ($q) use ($filters) {
                    $q->where('estado', 'activo')
                        ->whereHas('seccion.grado', function ($gq) use ($filters) {
                            $gq->where('id', $filters['grade_id']);
                        });
                });
            }

            if ($filters['section_id'] ?? null) {
                $query->whereHas('alumno.matriculas', function ($q) use ($filters) {
                    $q->where('estado', 'activo')
                        ->where('seccion_id', $filters['section_id']);
                });
            }

            $obligations = $query->get();
            $count = 0;

            foreach ($obligations as $obligation) {
                try {
                    $this->adjust($obligation, $adjustmentData, $adjustedBy);
                    $count++;
                } catch (\Throwable $e) {
                    // Log but continue with other obligations
                    \Log::error("Failed to adjust obligation {$obligation->id}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return $count;
        });
    }

    /**
     * Calculate new values based on adjustment type.
     *
     * @param ObligacionPago $obligation
     * @param string $type charge, discount, or waiver
     * @param float $amount
     *
     * @return array
     */
    private function calculateAdjustment(
        ObligacionPago $obligation,
        string $type,
        float $amount
    ): array {
        return match ($type) {
            'charge' => $this->applyCharge($obligation, $amount),
            'discount' => $this->applyDiscount($obligation, $amount),
            'waiver' => $this->applyWaiver($obligation),
            default => throw new \InvalidArgumentException("Unknown adjustment type: {$type}"),
        };
    }

    /**
     * Apply charge adjustment (increase amount).
     *
     * @param ObligacionPago $obligation
     * @param float $chargeAmount
     *
     * @return array
     */
    private function applyCharge(ObligacionPago $obligation, float $chargeAmount): array
    {
        return [
            'monto_ordinario_snapshot' => $obligation->monto_ordinario_snapshot + $chargeAmount,
            'monto_pronto_pago_snapshot' => max(0, $obligation->monto_pronto_pago_snapshot + $chargeAmount),
        ];
    }

    /**
     * Apply discount adjustment (decrease amount).
     *
     * @param ObligacionPago $obligation
     * @param float $discountAmount
     *
     * @return array
     */
    private function applyDiscount(ObligacionPago $obligation, float $discountAmount): array
    {
        $newOrdinario = max(0, $obligation->monto_ordinario_snapshot - $discountAmount);
        $newPromtoPago = max(0, $newOrdinario - $obligation->descuento_pronto_pago_aplicado);

        return [
            'monto_ordinario_snapshot' => $newOrdinario,
            'monto_pronto_pago_snapshot' => $newPromtoPago,
        ];
    }

    /**
     * Apply waiver adjustment (zero out amounts).
     *
     * @param ObligacionPago $obligation
     *
     * @return array
     */
    private function applyWaiver(ObligacionPago $obligation): array
    {
        return [
            'monto_ordinario_snapshot' => 0,
            'monto_pronto_pago_snapshot' => 0,
        ];
    }
}
