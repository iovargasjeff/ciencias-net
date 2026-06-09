<?php

namespace App\Modules\Finanzas\Application;

use App\Modules\Academico\Infrastructure\Models\PeriodoAcademico;
use App\Modules\Finanzas\Infrastructure\Models\BeneficioAlumno;
use App\Modules\Finanzas\Infrastructure\Models\ConceptoPago;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class FinanceConfigurationService
{
    public function createConcept(array $data, User $user): ConceptoPago
    {
        $period = $this->resolveAcademicPeriod($data['academic_period_id'] ?? null);

        return DB::transaction(function () use ($data, $period, $user): ConceptoPago {
            if (ConceptoPago::query()
                ->where('periodo_academico_id', $period->id)
                ->where('codigo', $data['code'])
                ->whereNull('vigente_hasta')
                ->exists()) {
                throw new ConflictHttpException('Ya existe un concepto activo con ese código para el periodo.');
            }

            return ConceptoPago::create([
                'codigo' => $data['code'],
                'nombre' => $data['name'],
                'tipo' => $data['type'],
                'periodo_academico_id' => $period->id,
                'periodo_anio' => $data['year'],
                'periodo_mes' => $data['month'] ?? null,
                'monto_base' => $data['amount'],
                'descuento_pronto_pago' => $data['early_payment_discount'] ?? 0,
                'fecha_limite_pronto_pago' => $data['early_payment_deadline'] ?? null,
                'estado' => 'vigente',
                'vigente_desde' => now()->toDateString(),
                'creado_por' => $user->id,
            ]);
        });
    }

    public function updateConcept(ConceptoPago $concept, array $data, User $user): ConceptoPago
    {
        if ($concept->estado === 'cerrado') {
            throw new ConflictHttpException('El concepto de pago ya está cerrado.');
        }

        return DB::transaction(function () use ($concept, $data, $user): ConceptoPago {
            $updates = $this->conceptUpdates($concept, $data);

            if ($concept->obligacionesPago()->exists()) {
                $concept->update([
                    'estado' => 'cerrado',
                    'bloqueado_en' => now(),
                    'vigente_hasta' => now()->toDateString(),
                ]);

                return ConceptoPago::create(array_merge($concept->only([
                    'codigo',
                    'nombre',
                    'tipo',
                    'periodo_academico_id',
                    'periodo_anio',
                    'periodo_mes',
                    'monto_base',
                    'descuento_pronto_pago',
                    'fecha_limite_pronto_pago',
                ]), $updates, [
                    'estado' => 'vigente',
                    'vigente_desde' => now()->toDateString(),
                    'reemplaza_concepto_id' => $concept->id,
                    'creado_por' => $user->id,
                ]));
            }

            $concept->update($updates);

            return $concept->refresh();
        });
    }

    public function createBenefit(array $data, User $user): BeneficioAlumno
    {
        return DB::transaction(function () use ($data, $user): BeneficioAlumno {
            $concepts = ConceptoPago::query()
                ->whereIn('id', $data['concept_ids'] ?? [])
                ->get();

            $benefit = BeneficioAlumno::create(array_merge(
                $this->benefitScopeFlags($concepts),
                [
                    'alumno_id' => $data['student_id'],
                    'tipo' => $data['benefit_type'] === 'waiver' ? 'beca' : 'descuento',
                    'modalidad' => match ($data['benefit_type']) {
                        'percentage' => 'porcentaje',
                        'fixed' => 'monto_fijo',
                        default => 'exoneracion',
                    },
                    'valor' => $data['benefit_type'] === 'waiver' ? null : $data['value'],
                    'acumulable_pronto_pago' => (bool) ($data['stackable_with_early_payment'] ?? false),
                    'vigente_desde' => $data['starts_on'],
                    'vigente_hasta' => $data['ends_on'] ?? null,
                    'motivo' => $data['reason'],
                    'activo' => true,
                    'registrado_por' => $user->id,
                ],
            ));

            if ($concepts->isNotEmpty()) {
                $benefit->conceptos()->sync($concepts->modelKeys());
            }

            return $benefit->load('conceptos');
        });
    }

    public function deactivateBenefit(BeneficioAlumno $benefit, string $reason): BeneficioAlumno
    {
        return DB::transaction(function () use ($benefit, $reason): BeneficioAlumno {
            $benefit->update([
                'activo' => false,
                'vigente_hasta' => $benefit->vigente_hasta ?? now()->toDateString(),
                'motivo' => $reason,
            ]);

            return $benefit->load('conceptos');
        });
    }

    private function resolveAcademicPeriod(?string $periodId): PeriodoAcademico
    {
        $query = PeriodoAcademico::query();
        $period = $periodId !== null
            ? $query->whereKey($periodId)->first()
            : $query->where('estado', 'activo')->latest('fecha_inicio')->first();

        if ($period === null) {
            throw ValidationException::withMessages([
                'academic_period_id' => ['Debe existir un periodo académico.'],
            ]);
        }

        return $period;
    }

    private function conceptUpdates(ConceptoPago $concept, array $data): array
    {
        $updates = [];
        $map = [
            'name' => 'nombre',
            'amount' => 'monto_base',
            'type' => 'tipo',
            'year' => 'periodo_anio',
            'month' => 'periodo_mes',
            'early_payment_discount' => 'descuento_pronto_pago',
            'early_payment_deadline' => 'fecha_limite_pronto_pago',
        ];

        foreach ($map as $requestKey => $column) {
            if (array_key_exists($requestKey, $data)) {
                $updates[$column] = $data[$requestKey];
            }
        }

        if (($updates['tipo'] ?? $concept->tipo) === 'mensualidad' && ($updates['periodo_mes'] ?? $concept->periodo_mes) === null) {
            throw ValidationException::withMessages(['month' => ['El mes es obligatorio para mensualidades.']]);
        }

        if ((float) ($updates['descuento_pronto_pago'] ?? $concept->descuento_pronto_pago) > (float) ($updates['monto_base'] ?? $concept->monto_base)) {
            throw ValidationException::withMessages([
                'early_payment_discount' => ['El descuento por pronto pago no puede superar el monto.'],
            ]);
        }

        return $updates;
    }

    private function benefitScopeFlags($concepts): array
    {
        if ($concepts->isEmpty()) {
            return [
                'aplica_mensualidad' => true,
                'aplica_matricula' => false,
                'aplica_cuota_ingreso' => false,
            ];
        }

        return [
            'aplica_mensualidad' => $concepts->contains('tipo', 'mensualidad'),
            'aplica_matricula' => $concepts->contains('tipo', 'matricula'),
            'aplica_cuota_ingreso' => $concepts->contains('tipo', 'cuota_ingreso'),
        ];
    }
}
