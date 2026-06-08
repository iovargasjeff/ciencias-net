<?php

namespace App\Modules\Finanzas\Domain\Services;

use App\Modules\Academico\Infrastructure\Models\PeriodoAcademico;
use App\Modules\Finanzas\Domain\ValueObjects\ObligationSnapshot;
use App\Modules\Finanzas\Infrastructure\Models\BeneficioAlumno;
use App\Modules\Finanzas\Infrastructure\Models\ConceptoPago;
use App\Modules\Finanzas\Infrastructure\Models\ObligacionPago;
use App\Modules\Usuarios\Infrastructure\Models\Alumno;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Service for generating payment obligations (deudas) with transactional integrity.
 *
 * Handles:
 * - Resolving single benefit per student
 * - Calculating and freezing obligation snapshots
 * - Transactional all-or-nothing generation
 * - Idempotency via generated tracking
 */
class ObligationGenerationService
{
    /**
     * @throws ConflictHttpException If concept not vigente or period not active
     */
    public function generate(
        string $conceptId,
        string $periodId,
        Carbon $dueDate,
        ?array $studentIds,
        User $generatedBy
    ): Collection {
        return DB::transaction(function () use ($conceptId, $periodId, $dueDate, $studentIds, $generatedBy) {
            $concept = $this->getAndValidateConcept($conceptId, $periodId);

            $period = $this->getAndValidatePeriod($periodId);

            $students = $this->resolveStudents($periodId, $studentIds);

            if ($students->isEmpty()) {
                return Collection::make();
            }

            $obligations = $students->map(function (Alumno $student) use ($concept, $dueDate, $generatedBy): ObligacionPago {
                return $this->generateForStudent($student, $concept, $dueDate, $generatedBy);
            });

            return $obligations;
        });
    }

    private function getAndValidateConcept(string $conceptId, string $periodId): ConceptoPago
    {
        $concept = ConceptoPago::query()
            ->where('id', $conceptId)
            ->where('periodo_academico_id', $periodId)
            ->first();

        if (! $concept) {
            throw new ConflictHttpException('Concepto de pago no encontrado para el período.');
        }

        if ($concept->estado !== 'vigente') {
            throw new ConflictHttpException('El concepto de pago debe estar en estado vigente.');
        }

        return $concept;
    }

    private function getAndValidatePeriod(string $periodId): PeriodoAcademico
    {
        $period = PeriodoAcademico::findOr($periodId, function () {
            throw new ConflictHttpException('Período académico no encontrado.');
        });

        if ($period->estado !== 'activo') {
            throw new ConflictHttpException('El período académico debe estar activo.');
        }

        return $period;
    }

    private function resolveStudents(string $periodId, ?array $studentIds): Collection
    {
        $query = Alumno::query()
            ->join('matriculas', 'alumnos.id', '=', 'matriculas.alumno_id')
            ->join('secciones', 'matriculas.seccion_id', '=', 'secciones.id')
            ->join('grados', 'secciones.grado_id', '=', 'grados.id')
            ->where('grados.periodo_academico_id', $periodId)
            ->where('matriculas.estado', 'activo')
            ->select('alumnos.*')
            ->distinct();

        if ($studentIds && ! empty($studentIds)) {
            $query->whereIn('alumnos.id', $studentIds);
        }

        return $query->get();
    }

    private function generateForStudent(
        Alumno $student,
        ConceptoPago $concept,
        Carbon $dueDate,
        User $generatedBy
    ): ObligacionPago {
        $existing = ObligacionPago::query()
            ->where('alumno_id', $student->id)
            ->where('concepto_id', $concept->id)
            ->where('estado', 'pendiente')
            ->first();

        if ($existing) {
            return $existing;
        }

        $benefit = $this->resolveBenefit($student, $concept);

        $snapshot = ObligationSnapshot::fromConceptAndBenefit(
            $concept->only('id', 'monto_base', 'descuento_pronto_pago', 'fecha_limite_pronto_pago'),
            $benefit?->only('id', 'modalidad', 'valor'),
            $dueDate
        );

        return ObligacionPago::create(array_merge(
            $snapshot->toArray(),
            [
                'alumno_id' => $student->id,
                'concepto_id' => $concept->id,
                'estado' => 'pendiente',
                'registrado_por' => $generatedBy->id,
            ]
        ));
    }

    private function resolveBenefit(Alumno $student, ConceptoPago $concept): ?BeneficioAlumno
    {
        $applicableBenefits = BeneficioAlumno::query()
            ->where('alumno_id', $student->id)
            ->where('activo', true)
            ->where('vigente_desde', '<=', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('vigente_hasta')
                    ->orWhere('vigente_hasta', '>=', now()->toDateString());
            })
            ->get();

        $filtered = $applicableBenefits->filter(function (BeneficioAlumno $benefit) use ($concept) {
            return match ($concept->tipo) {
                'mensualidad' => $benefit->aplica_mensualidad,
                'matricula' => $benefit->aplica_matricula,
                'cuota_ingreso' => $benefit->aplica_cuota_ingreso,
                default => false,
            };
        });

        return $filtered->first();
    }
}
