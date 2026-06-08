<?php

namespace App\Modules\Finanzas\Application\DTOs;

/**
 * Data Transfer Object for obligation adjustment request.
 */
class AdjustmentDTO
{
    public function __construct(
        public readonly string $adjustmentType,  // charge, discount, waiver
        public readonly float $amount,
        public readonly string $reason,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            adjustmentType: $data['adjustment_type'],
            amount: (float) $data['amount'],
            reason: $data['reason'],
        );
    }
}
