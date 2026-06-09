<?php

namespace App\Modules\Finanzas\Application\DTOs;

use Carbon\Carbon;

/**
 * Data Transfer Object for obligation generation request.
 */
class GenerateObligationsDTO
{
    public function __construct(
        public readonly string $conceptId,
        public readonly string $academicPeriodId,
        public readonly Carbon $dueDate,
        public readonly ?array $studentIds = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            conceptId: $data['concept_id'],
            academicPeriodId: $data['academic_period_id'],
            dueDate: Carbon::parse($data['due_date']),
            studentIds: $data['student_ids'] ?? null,
        );
    }
}
