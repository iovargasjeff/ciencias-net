<?php

use App\Modules\Academico\Infrastructure\Models\PeriodoAcademico;
use App\Modules\Finanzas\Infrastructure\Models\BeneficioAlumno;
use App\Modules\Finanzas\Infrastructure\Models\ConceptoPago;
use App\Modules\Finanzas\Infrastructure\Models\ObligacionPago;
use App\Modules\Usuarios\Infrastructure\Models\Alumno;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function financeManager(): User
{
    $user = User::factory()->create();
    $user->givePermissionTo('gestionar_finanzas');

    return $user;
}

function financePeriod(User $operator, string $status = 'activo'): PeriodoAcademico
{
    return PeriodoAcademico::factory()->create([
        'estado' => $status,
        'creado_por' => $operator->id,
        'fecha_inicio' => '2026-03-01',
        'fecha_fin' => '2026-12-20',
    ]);
}

function financeConcept(User $operator, PeriodoAcademico $period, array $overrides = []): ConceptoPago
{
    return ConceptoPago::create(array_merge([
        'codigo' => 'MENSUALIDAD-2026-04',
        'nombre' => 'Mensualidad Abril',
        'tipo' => 'mensualidad',
        'periodo_academico_id' => $period->id,
        'periodo_anio' => 2026,
        'periodo_mes' => 4,
        'monto_base' => 480.00,
        'descuento_pronto_pago' => 30.00,
        'fecha_limite_pronto_pago' => '2026-04-10',
        'estado' => 'vigente',
        'vigente_desde' => '2026-03-01',
        'creado_por' => $operator->id,
    ], $overrides));
}

it('requires gestionar_finanzas permission for finance configuration endpoints', function () {
    $user = User::factory()->create();
    $operator = financeManager();
    $period = financePeriod($operator);

    $this->actingAs($user)
        ->postJson('/api/v1/payment-concepts', [
            'code' => 'MAT-2026',
            'name' => 'Matrícula 2026',
            'amount' => '480.00',
            'academic_period_id' => $period->id,
            'type' => 'matricula',
            'year' => 2026,
        ])
        ->assertForbidden();
});

it('creates a payment concept and audits the operation', function () {
    $manager = financeManager();
    $period = financePeriod($manager);

    $this->actingAs($manager)
        ->postJson('/api/v1/payment-concepts', [
            'code' => 'MENSUALIDAD-2026-04',
            'name' => 'Mensualidad Abril',
            'amount' => '480.00',
            'academic_period_id' => $period->id,
            'type' => 'mensualidad',
            'year' => 2026,
            'month' => 4,
            'early_payment_discount' => '30.00',
            'early_payment_deadline' => '2026-04-10',
        ])
        ->assertCreated()
        ->assertJsonPath('data.code', 'MENSUALIDAD-2026-04')
        ->assertJsonPath('data.amount', '480.00');

    $this->assertDatabaseHas('conceptos_pago', [
        'codigo' => 'MENSUALIDAD-2026-04',
        'tipo' => 'mensualidad',
        'periodo_mes' => 4,
    ]);
    $this->assertDatabaseHas('audit_logs', ['action' => 'finance.concept_created']);
});

it('validates monthly concepts and early payment discount amounts', function () {
    $manager = financeManager();
    $period = financePeriod($manager);

    $payload = [
        'code' => 'MENSUALIDAD-2026-05',
        'name' => 'Mensualidad Mayo',
        'amount' => '480.00',
        'academic_period_id' => $period->id,
        'type' => 'mensualidad',
        'year' => 2026,
    ];

    $this->actingAs($manager)
        ->postJson('/api/v1/payment-concepts', $payload)
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'validation_failed')
        ->assertJsonPath('error.fields.month.0', 'El mes es obligatorio para mensualidades.');

    $this->actingAs($manager)
        ->postJson('/api/v1/payment-concepts', array_merge($payload, [
            'month' => 5,
            'early_payment_discount' => '481.00',
        ]))
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'validation_failed')
        ->assertJsonPath('error.fields.early_payment_discount.0', 'El descuento por pronto pago no puede superar el monto.');
});

it('versions concepts with obligations without altering historical snapshots', function () {
    $manager = financeManager();
    $period = financePeriod($manager);
    $student = Alumno::factory()->create();
    $concept = financeConcept($manager, $period);

    $obligation = ObligacionPago::create([
        'alumno_id' => $student->id,
        'concepto_id' => $concept->id,
        'monto_base_snapshot' => 480.00,
        'monto_beneficio_snapshot' => 0,
        'descuento_pronto_pago_aplicado' => 30.00,
        'monto_ordinario_snapshot' => 480.00,
        'monto_pronto_pago_snapshot' => 450.00,
        'fecha_limite_pronto_pago_snapshot' => '2026-04-10',
        'fecha_vencimiento' => '2026-04-30',
        'estado' => 'pendiente',
        'registrado_por' => $manager->id,
    ]);

    $newConceptId = $this->actingAs($manager)
        ->patchJson("/api/v1/payment-concepts/{$concept->id}", [
            'amount' => '500.00',
            'early_payment_discount' => '20.00',
        ])
        ->assertOk()
        ->assertJsonPath('data.replaces_concept_id', $concept->id)
        ->json('data.id');

    expect($newConceptId)->not->toBe($concept->id)
        ->and($concept->fresh()->estado)->toBe('cerrado')
        ->and($obligation->fresh()->concepto_id)->toBe($concept->id)
        ->and($obligation->fresh()->monto_base_snapshot)->toBe('480.00')
        ->and(ConceptoPago::findOrFail($newConceptId)->monto_base)->toBe('500.00');
});

it('rejects invalid benefit modality values', function () {
    $manager = financeManager();
    $student = Alumno::factory()->create();

    $this->actingAs($manager)
        ->postJson('/api/v1/student-benefits', [
            'student_id' => $student->id,
            'benefit_type' => 'percentage',
            'value' => '150.00',
            'starts_on' => '2026-03-01',
            'reason' => 'Beca inválida',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'validation_failed')
        ->assertJsonPath('error.fields.value.0', 'El porcentaje debe ser mayor que 0 y menor o igual a 100.');

    $this->actingAs($manager)
        ->postJson('/api/v1/student-benefits', [
            'student_id' => $student->id,
            'benefit_type' => 'waiver',
            'value' => '10.00',
            'starts_on' => '2026-03-01',
            'reason' => 'Exoneración inválida',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'validation_failed')
        ->assertJsonPath('error.fields.value.0', 'La exoneración no debe enviar valor.');
});

it('stores explicit benefit concept scope', function () {
    $manager = financeManager();
    $period = financePeriod($manager);
    $student = Alumno::factory()->create();
    $monthly = financeConcept($manager, $period);
    $enrollment = financeConcept($manager, $period, [
        'codigo' => 'MATRICULA-2026',
        'nombre' => 'Matrícula 2026',
        'tipo' => 'matricula',
        'periodo_mes' => null,
    ]);

    $benefitId = $this->actingAs($manager)
        ->postJson('/api/v1/student-benefits', [
            'student_id' => $student->id,
            'benefit_type' => 'fixed',
            'value' => '40.00',
            'concept_ids' => [$monthly->id, $enrollment->id],
            'stackable_with_early_payment' => true,
            'starts_on' => '2026-03-01',
            'reason' => 'Convenio familiar',
        ])
        ->assertCreated()
        ->assertJsonPath('data.stackable_with_early_payment', true)
        ->json('data.id');

    $benefit = BeneficioAlumno::with('conceptos')->findOrFail($benefitId);
    expect($benefit->conceptos)->toHaveCount(2)
        ->and($benefit->aplica_mensualidad)->toBeTrue()
        ->and($benefit->aplica_matricula)->toBeTrue()
        ->and($benefit->aplica_cuota_ingreso)->toBeFalse();
});

it('deactivates benefits for future use without touching historical obligations', function () {
    $manager = financeManager();
    $period = financePeriod($manager);
    $student = Alumno::factory()->create();
    $concept = financeConcept($manager, $period);
    $benefit = BeneficioAlumno::create([
        'alumno_id' => $student->id,
        'tipo' => 'descuento',
        'modalidad' => 'monto_fijo',
        'valor' => 20.00,
        'aplica_mensualidad' => true,
        'motivo' => 'Convenio',
        'vigente_desde' => '2026-03-01',
        'registrado_por' => $manager->id,
    ]);
    $obligation = ObligacionPago::create([
        'alumno_id' => $student->id,
        'concepto_id' => $concept->id,
        'monto_base_snapshot' => 480.00,
        'beneficio_id' => $benefit->id,
        'monto_beneficio_snapshot' => 20.00,
        'descuento_pronto_pago_aplicado' => 30.00,
        'monto_ordinario_snapshot' => 460.00,
        'monto_pronto_pago_snapshot' => 430.00,
        'fecha_limite_pronto_pago_snapshot' => '2026-04-10',
        'fecha_vencimiento' => '2026-04-30',
        'estado' => 'pendiente',
        'registrado_por' => $manager->id,
    ]);

    $this->actingAs($manager)
        ->postJson("/api/v1/student-benefits/{$benefit->id}/deactivation", [
            'reason' => 'Fin del convenio',
        ])
        ->assertOk()
        ->assertJsonPath('data.active', false);

    expect($benefit->fresh()->activo)->toBeFalse()
        ->and($obligation->fresh()->beneficio_id)->toBe($benefit->id)
        ->and($obligation->fresh()->monto_beneficio_snapshot)->toBe('20.00');
    $this->assertDatabaseHas('audit_logs', ['action' => 'finance.benefit_deactivated']);
});
