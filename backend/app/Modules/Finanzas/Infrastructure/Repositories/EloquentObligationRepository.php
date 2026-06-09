<?php

namespace App\Modules\Finanzas\Infrastructure\Repositories;

use App\Modules\Finanzas\Domain\Repositories\ObligationRepositoryInterface;
use App\Modules\Finanzas\Infrastructure\Models\ObligacionPago;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Collection;

class EloquentObligationRepository implements ObligationRepositoryInterface
{
    public function paginated(array $filters = [], int $perPage = 20): Paginator
    {
        $query = ObligacionPago::query()
            ->with('alumno', 'concepto', 'beneficio')
            ->latest('created_at');

        // Filter by student
        if ($filters['student_id'] ?? null) {
            $query->where('alumno_id', $filters['student_id']);
        }

        // Filter by concept
        if ($filters['concept_id'] ?? null) {
            $query->where('concepto_id', $filters['concept_id']);
        }

        // Filter by state
        if ($filters['estado'] ?? null) {
            $query->where('estado', $filters['estado']);
        }

        // Filter by due date range
        if ($filters['due_date_from'] ?? null) {
            $query->where('fecha_vencimiento', '>=', $filters['due_date_from']);
        }

        if ($filters['due_date_to'] ?? null) {
            $query->where('fecha_vencimiento', '<=', $filters['due_date_to']);
        }

        return $query->paginate($perPage);
    }

    public function pendingByStudent(string $studentId): Collection
    {
        return ObligacionPago::query()
            ->where('alumno_id', $studentId)
            ->where('estado', 'pendiente')
            ->latest('fecha_vencimiento')
            ->get();
    }

    public function byConceptAndPeriod(string $conceptId, ?string $periodId = null): Collection
    {
        $query = ObligacionPago::query()
            ->where('concepto_id', $conceptId);

        if ($periodId) {
            $query->whereHas('concepto', function ($q) use ($periodId) {
                $q->where('periodo_academico_id', $periodId);
            });
        }

        return $query->get();
    }

    public function findOrFail(string $id): ObligacionPago
    {
        return ObligacionPago::with('alumno', 'concepto', 'beneficio')
            ->findOrFail($id);
    }

    public function bulkFilter(array $filters): Collection
    {
        $query = ObligacionPago::query()
            ->where('estado', 'pendiente');

        // Direct obligation IDs
        if (! empty($filters['obligation_ids'])) {
            $query->whereIn('id', $filters['obligation_ids']);
        }

        // By concept
        if ($filters['concept_id'] ?? null) {
            $query->where('concepto_id', $filters['concept_id']);
        }

        // By grade
        if ($filters['grade_id'] ?? null) {
            $query->whereHas('alumno.matriculas', function ($q) use ($filters) {
                $q->where('estado', 'activo')
                    ->whereHas('seccion.grado', function ($gq) use ($filters) {
                        $gq->where('id', $filters['grade_id']);
                    });
            });
        }

        // By section
        if ($filters['section_id'] ?? null) {
            $query->whereHas('alumno.matriculas', function ($q) use ($filters) {
                $q->where('estado', 'activo')
                    ->where('seccion_id', $filters['section_id']);
            });
        }

        return $query->get();
    }
}
