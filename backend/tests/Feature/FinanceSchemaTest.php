<?php

/**
 * DB-003: add-finance-schema
 *
 * Verifica:
 * - Las obligaciones congelan snapshots de conceptos y beneficios.
 * - Constraints CHECK PostgreSQL para montos, meses, beneficios y movimientos.
 * - Referencias de pago únicas por medio y referencia.
 * - Relaciones principales alumno → obligaciones → movimientos.
 */

use App\Modules\Academico\Infrastructure\Models\PeriodoAcademico;
use App\Modules\Finanzas\Infrastructure\Models\BeneficioAlumno;
use App\Modules\Finanzas\Infrastructure\Models\ConceptoPago;
use App\Modules\Finanzas\Infrastructure\Models\MovimientoPago;
use App\Modules\Finanzas\Infrastructure\Models\ObligacionPago;
use App\Modules\Usuarios\Infrastructure\Models\Alumno;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function buildFinanceContext(): array
{
    $operator = User::factory()->create();
    $student = Alumno::factory()->create();
    $period = PeriodoAcademico::factory()->create([
        'creado_por' => $operator->id,
        'nombre' => 'Año lectivo 2026',
        'fecha_inicio' => '2026-03-01',
        'fecha_fin' => '2026-12-20',
    ]);

    $concept = ConceptoPago::create([
        'nombre' => 'Mensualidad Abril',
        'tipo' => 'mensualidad',
        'periodo_academico_id' => $period->id,
        'periodo_anio' => 2026,
        'periodo_mes' => 4,
        'monto_base' => 480.00,
        'descuento_pronto_pago' => 30.00,
        'fecha_limite_pronto_pago' => '2026-04-10',
        'estado' => 'vigente',
        'creado_por' => $operator->id,
    ]);

    $benefit = BeneficioAlumno::create([
        'alumno_id' => $student->id,
        'tipo' => 'descuento',
        'modalidad' => 'monto_fijo',
        'valor' => 20.00,
        'aplica_mensualidad' => true,
        'motivo' => 'Acuerdo especial',
        'vigente_desde' => '2026-03-01',
        'registrado_por' => $operator->id,
    ]);

    return compact('operator', 'student', 'period', 'concept', 'benefit');
}

function createFinanceObligation(array $context): ObligacionPago
{
    return ObligacionPago::create([
        'alumno_id' => $context['student']->id,
        'concepto_id' => $context['concept']->id,
        'monto_base_snapshot' => 480.00,
        'beneficio_id' => $context['benefit']->id,
        'monto_beneficio_snapshot' => 20.00,
        'descuento_pronto_pago_aplicado' => 30.00,
        'monto_ordinario_snapshot' => 460.00,
        'monto_pronto_pago_snapshot' => 430.00,
        'fecha_limite_pronto_pago_snapshot' => '2026-04-10',
        'fecha_vencimiento' => '2026-04-30',
        'estado' => 'pendiente',
        'registrado_por' => $context['operator']->id,
    ]);
}

it('freezes concept and benefit snapshots on payment obligations', function () {
    $context = buildFinanceContext();
    $obligation = createFinanceObligation($context);

    $context['concept']->update([
        'monto_base' => 500.00,
        'descuento_pronto_pago' => 10.00,
        'fecha_limite_pronto_pago' => '2026-04-05',
    ]);
    $context['benefit']->update(['valor' => 5.00]);

    $obligation->refresh();

    expect($obligation->monto_base_snapshot)->toBe('480.00')
        ->and($obligation->monto_beneficio_snapshot)->toBe('20.00')
        ->and($obligation->descuento_pronto_pago_aplicado)->toBe('30.00')
        ->and($obligation->monto_ordinario_snapshot)->toBe('460.00')
        ->and($obligation->monto_pronto_pago_snapshot)->toBe('430.00')
        ->and($obligation->fecha_limite_pronto_pago_snapshot->toDateString())->toBe('2026-04-10');
});

it('traverses student payment obligations and movements', function () {
    $context = buildFinanceContext();
    $obligation = createFinanceObligation($context);

    MovimientoPago::create([
        'obligacion_pago_id' => $obligation->id,
        'tipo' => 'pago',
        'monto' => 430.00,
        'medio_pago' => 'efectivo',
        'numero_recibo' => 'REC-2026-0001',
        'registrado_por' => $context['operator']->id,
    ]);

    $student = Alumno::with('obligacionesPago.movimientosPago')->find($context['student']->id);

    expect($student->obligacionesPago)->toHaveCount(1)
        ->and($student->obligacionesPago->first()->movimientosPago)->toHaveCount(1)
        ->and($student->obligacionesPago->first()->movimientosPago->first()->monto)->toBe('430.00');
});

it('rejects a monthly concept without periodo_mes on PostgreSQL', function () {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('CHECK constraints only enforced in PostgreSQL');
    }

    $operator = User::factory()->create();
    $period = PeriodoAcademico::factory()->create(['creado_por' => $operator->id]);

    expect(fn () => ConceptoPago::create([
        'nombre' => 'Mensualidad sin mes',
        'tipo' => 'mensualidad',
        'periodo_academico_id' => $period->id,
        'periodo_anio' => 2026,
        'monto_base' => 480.00,
        'estado' => 'vigente',
        'creado_por' => $operator->id,
    ]))->toThrow(QueryException::class);
});

it('rejects invalid benefit values on PostgreSQL', function () {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('CHECK constraints only enforced in PostgreSQL');
    }

    $operator = User::factory()->create();
    $student = Alumno::factory()->create();

    expect(fn () => BeneficioAlumno::create([
        'alumno_id' => $student->id,
        'tipo' => 'descuento',
        'modalidad' => 'porcentaje',
        'valor' => 150.00,
        'aplica_mensualidad' => true,
        'motivo' => 'Porcentaje inválido',
        'vigente_desde' => '2026-03-01',
        'registrado_por' => $operator->id,
    ]))->toThrow(QueryException::class);

    expect(fn () => BeneficioAlumno::create([
        'alumno_id' => $student->id,
        'tipo' => 'descuento',
        'modalidad' => 'monto_fijo',
        'valor' => 0,
        'aplica_mensualidad' => true,
        'motivo' => 'Monto inválido',
        'vigente_desde' => '2026-03-01',
        'registrado_por' => $operator->id,
    ]))->toThrow(QueryException::class);

    expect(fn () => BeneficioAlumno::create([
        'alumno_id' => $student->id,
        'tipo' => 'beca',
        'modalidad' => 'exoneracion',
        'valor' => 10.00,
        'aplica_mensualidad' => true,
        'motivo' => 'Exoneración inválida',
        'vigente_desde' => '2026-03-01',
        'registrado_por' => $operator->id,
    ]))->toThrow(QueryException::class);
});

it('rejects negative obligation amounts on PostgreSQL', function () {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('CHECK constraints only enforced in PostgreSQL');
    }

    $context = buildFinanceContext();

    expect(fn () => ObligacionPago::create([
        'alumno_id' => $context['student']->id,
        'concepto_id' => $context['concept']->id,
        'monto_base_snapshot' => -1,
        'beneficio_id' => $context['benefit']->id,
        'monto_beneficio_snapshot' => 20.00,
        'descuento_pronto_pago_aplicado' => 30.00,
        'monto_ordinario_snapshot' => 460.00,
        'monto_pronto_pago_snapshot' => 430.00,
        'fecha_limite_pronto_pago_snapshot' => '2026-04-10',
        'fecha_vencimiento' => '2026-04-30',
        'estado' => 'pendiente',
        'registrado_por' => $context['operator']->id,
    ]))->toThrow(QueryException::class);
});

it('rejects duplicate payment references for the same method on PostgreSQL', function () {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('Partial unique indexes only verified in PostgreSQL');
    }

    $context = buildFinanceContext();
    $firstObligation = createFinanceObligation($context);
    $secondObligation = createFinanceObligation($context);

    MovimientoPago::create([
        'obligacion_pago_id' => $firstObligation->id,
        'tipo' => 'pago',
        'monto' => 430.00,
        'medio_pago' => 'yape',
        'referencia' => 'YAPE-001',
        'numero_recibo' => 'REC-2026-0002',
        'registrado_por' => $context['operator']->id,
    ]);

    expect(fn () => MovimientoPago::create([
        'obligacion_pago_id' => $secondObligation->id,
        'tipo' => 'pago',
        'monto' => 430.00,
        'medio_pago' => 'yape',
        'referencia' => 'YAPE-001',
        'numero_recibo' => 'REC-2026-0003',
        'registrado_por' => $context['operator']->id,
    ]))->toThrow(QueryException::class);
});

it('allows cash payments without reference on PostgreSQL', function () {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('Payment method CHECK constraints only verified in PostgreSQL');
    }

    $context = buildFinanceContext();
    $obligation = createFinanceObligation($context);

    $movement = MovimientoPago::create([
        'obligacion_pago_id' => $obligation->id,
        'tipo' => 'pago',
        'monto' => 430.00,
        'medio_pago' => 'efectivo',
        'numero_recibo' => 'REC-2026-0004',
        'registrado_por' => $context['operator']->id,
    ]);

    expect($movement->referencia)->toBeNull();
});
