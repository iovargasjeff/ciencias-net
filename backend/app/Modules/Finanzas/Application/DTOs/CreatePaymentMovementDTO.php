<?php

namespace App\Modules\Finanzas\Application\DTOs;

use Carbon\Carbon;

class CreatePaymentMovementDTO
{
    public function __construct(
        public readonly string $obligationId,
        public readonly string $movementType,
        public readonly float $amount,
        public readonly Carbon $occurredAt,
        public readonly string $method,
        public readonly ?string $reference = null,
        public readonly ?string $reason = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            obligationId: $data['obligation_id'],
            movementType: $data['movement_type'],
            amount: (float) $data['amount'],
            occurredAt: Carbon::parse($data['occurred_at']),
            method: $data['method'],
            reference: $data['reference'] ?? null,
            reason: $data['reason'] ?? null,
        );
    }
}
