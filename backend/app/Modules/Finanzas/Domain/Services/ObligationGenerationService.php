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
     * Generate payment obligations for students in a period.
     *
     * @param string $conceptId UUID of concept to generate
     * @param string $periodId UUID of academic period
     * @param Carbon $dueDate Due date for obligations
     * @param array|null $studentIds Optional list of student UUIDs; if null, all enrolled students
     * @param User $generatedBy User performing generation
     *
     * @return Collection<ObligacionPago> Created obligations
     *
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
            // Load and validate concept
            $concept = $this->getAndValidateConcept($conceptId, $periodId);

            // Load and validate period
            $period = $this->getAndValidatePeriod($periodId);

            // Resolve students to generate for
            $students = $this->resolveStudents($periodId, $studentIds);

            if ($students->isEmpty()) {
                return Collection::make();
            }

            // Generate obligations for each student
            $obligations = $students->map(function (Alumno $student) use ($concept, $dueDate, $generatedBy): ObligacionPago {
                return $this->generateForStudent($student, $concept, $dueDate, $generatedBy);
            });

            return $obligations;
        });
    }

    /**
     * Validate and fetch concept.
     *
     * @param string $conceptId
     * @param string $periodId
     *
     * @return ConceptoPago
     *
     * @throws ConflictHttpException
     */
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

    /**
     * Validate and fetch academic period.
     *
     * @param string $periodId
     *
     * @return PeriodoAcademico
     *
     * @throws ConflictHttpException
     */
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

    /**
     * Resolve which students to generate obligations for.
     *
     * @param  string  $periodId
     * @param  ?array  $studentIds
     * @return Collection<Alumno>
     */
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

    /**
     * Generate single obligation for a student.
     *
     * @param  Alumno  $student
     * @param  ConceptoPago  $concept
     * @param  Carbon  $dueDate
     * @param  User  $generatedBy
     * @return ObligacionPago
     */
    private function generateForStudent(
        Alumno $student,
        ConceptoPago $concept,
        Carbon $dueDate,
        User $generatedBy
    ): ObligacionPago {
        // Check if obligation already exists
        $existing = ObligacionPago::query()
            ->where('alumno_id', $student->id)
            ->where('concepto_id', $concept->id)
            ->where('estado', 'pendiente')
            ->first();

        if ($existing) {
            return $existing;
        }

        // Resolve benefit for student (if applicable)
        $benefit = $this->resolveBenefit($student, $concept);

        // Create snapshot with frozen values
        $snapshot = ObligationSnapshot::fromConceptAndBenefit(
            $concept->only('id', 'monto_base', 'descuento_pronto_pago', 'fecha_limite_pronto_pago'),
            $benefit?->only('id', 'modalidad', 'valor'),
            $dueDate
        );

        // Create obligation with snapshot data
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

    /**
     * Resolve single applicable benefit for student and concept.
     *
     * @param  Alumno  $student
     * @param  ConceptoPago  $concept
     * @return ?BeneficioAlumno
     */
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

        // Filter benefits applicable to this concept type
        $filtered = $applicableBenefits->filter(function (BeneficioAlumno $benefit) use ($concept) {
            return match ($concept->tipo) {
                'mensualidad' => $benefit->aplica_mensualidad,
                'matricula' => $benefit->aplica_matricula,
                'cuota_ingreso' => $benefit->aplica_cuota_ingreso,
                default => false,
            };
        });

        // For now, return first applicable benefit; FE will handle selection if multiple
        // In production, might need to queue for admin selection
        return $filtered->first();
    }
}
