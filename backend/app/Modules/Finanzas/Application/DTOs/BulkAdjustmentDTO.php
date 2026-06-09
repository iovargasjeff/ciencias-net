<?php

namespace App\Modules\Finanzas\Application\DTOs;

/**
 * Data Transfer Object for bulk obligation adjustment request.
 */
class BulkAdjustmentDTO
{
    public function __construct(
        public readonly array $filters,
        public readonly string $adjustmentType,  // charge, discount, waiver
        public readonly float $amount,
        public readonly string $reason,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            filters: $data['filters'],
            adjustmentType: $data['adjustment_type'],
            amount: (float) $data['amount'],
            reason: $data['reason'],
        );
    }
}
