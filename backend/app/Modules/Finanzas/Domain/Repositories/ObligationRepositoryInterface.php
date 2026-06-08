<?php

namespace App\Modules\Finanzas\Domain\Repositories;

use App\Modules\Finanzas\Infrastructure\Models\ObligacionPago;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Collection;

interface ObligationRepositoryInterface
{
    public function paginated(array $filters = [], int $perPage = 20): Paginator;

    public function pendingByStudent(string $studentId): Collection;

    public function byConceptAndPeriod(string $conceptId, ?string $periodId = null): Collection;

    public function findOrFail(string $id): ObligacionPago;

    public function bulkFilter(array $filters): Collection;
}
