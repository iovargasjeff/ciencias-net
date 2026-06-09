<?php

namespace App\Modules\Finanzas\Domain\ValueObjects;

use Carbon\Carbon;

/**
 * Immutable value object representing frozen obligation amounts at generation time.
 * These values never change after obligation creation, ensuring historical accuracy.
 */
final class ObligationSnapshot
{
    public readonly string $conceptId;

    public readonly ?string $benefitId;

    public readonly float $montoBase;

    public readonly float $montoBeneficio;

    public readonly float $montoOrdinario;

    public readonly float $montoPromtoPago;

    public readonly float $descuentoPromtoPagoAplicado;

    public readonly Carbon $fechaLimitePromtoPago;

    public readonly Carbon $fechaVencimiento;

    private function __construct(
        string $conceptId,
        ?string $benefitId,
        float $montoBase,
        float $montoBeneficio,
        float $montoOrdinario,
        float $montoPromtoPago,
        float $descuentoPromtoPagoAplicado,
        Carbon $fechaLimitePromtoPago,
        Carbon $fechaVencimiento
    ) {
        $this->conceptId = $conceptId;
        $this->benefitId = $benefitId;
        $this->montoBase = $montoBase;
        $this->montoBeneficio = $montoBeneficio;
        $this->montoOrdinario = $montoOrdinario;
        $this->montoPromtoPago = $montoPromtoPago;
        $this->descuentoPromtoPagoAplicado = $descuentoPromtoPagoAplicado;
        $this->fechaLimitePromtoPago = $fechaLimitePromtoPago;
        $this->fechaVencimiento = $fechaVencimiento;
    }

    public static function fromConceptAndBenefit(
        array $conceptData,
        ?array $benefitData,
        Carbon $dueDate
    ): self {
        $montoBase = (float) $conceptData['monto_base'];
        $benefitId = $benefitData['id'] ?? null;

        $montoBeneficio = self::calculateBenefitAmount($montoBase, $benefitData);

        $montoOrdinario = $montoBase - $montoBeneficio;

        $descuentoPromtoPago = (float) ($conceptData['descuento_pronto_pago'] ?? 0);

        $montoPromtoPago = max(0, $montoOrdinario - $descuentoPromtoPago);

        return new self(
            $conceptData['id'],
            $benefitId,
            $montoBase,
            $montoBeneficio,
            $montoOrdinario,
            $montoPromtoPago,
            $descuentoPromtoPago,
            Carbon::parse($conceptData['fecha_limite_pronto_pago']),
            $dueDate
        );
    }

    private static function calculateBenefitAmount(float $baseAmount, ?array $benefitData): float
    {
        if (! $benefitData) {
            return 0.0;
        }

        return match ($benefitData['modalidad'] ?? null) {
            'porcentaje' => ($baseAmount * (float) $benefitData['valor']) / 100,
            'monto_fijo' => (float) $benefitData['valor'],
            'exoneracion' => $baseAmount,
            default => 0.0,
        };
    }

    public function toArray(): array
    {
        return [
            'monto_base_snapshot' => $this->montoBase,
            'beneficio_id' => $this->benefitId,
            'monto_beneficio_snapshot' => $this->montoBeneficio,
            'monto_ordinario_snapshot' => $this->montoOrdinario,
            'monto_pronto_pago_snapshot' => $this->montoPromtoPago,
            'descuento_pronto_pago_aplicado' => $this->descuentoPromtoPagoAplicado,
            'fecha_limite_pronto_pago_snapshot' => $this->fechaLimitePromtoPago->toDateString(),
            'fecha_vencimiento' => $this->fechaVencimiento->toDateString(),
        ];
    }
}
