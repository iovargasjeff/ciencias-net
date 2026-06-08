<?php

namespace App\Modules\Finanzas\Domain\Repositories;

use App\Modules\Finanzas\Infrastructure\Models\ObligacionPago;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Collection;

interface ObligationRepositoryInterface
{
    /**
     * Get paginated list of obligations with filters.
     *
     * @param array $filters student_id, concept_id, estado, due_date_from, due_date_to
     * @param int $perPage
     */
    public function paginated(array $filters = [], int $perPage = 20): Paginator;

    /**
     * Get pending obligations for a student.
     *
     * @param string $studentId
     * @return Collection<ObligacionPago>
     */
    public function pendingByStudent(string $studentId): Collection;

    /**
     * Get obligations by concept and optional period.
     *
     * @param string $conceptId
     * @param string|null $periodId
     * @return Collection<ObligacionPago>
     */
    public function byConceptAndPeriod(string $conceptId, ?string $periodId = null): Collection;

    /**
     * Find obligation by ID or fail.
     *
     * @param string $id
     */
    public function findOrFail(string $id): ObligacionPago;

    /**
     * Get obligations matching bulk adjustment filters.
     *
     * @param array $filters obligation_ids, concept_id, grade_id, section_id
     * @return Collection<ObligacionPago>
     */
    public function bulkFilter(array $filters): Collection;
}
