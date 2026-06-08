<?php

use App\Models\CargaAcademica;
use App\Models\Curso;
use App\Models\Docente;
use App\Models\Grado;
use App\Models\PeriodoAcademico;
use App\Models\Seccion;
use App\Models\User;
use App\Modules\Asistencia\Domain\Models\AsistenciaDocente;
use App\Modules\Asistencia\Domain\Models\SesionClase;
use App\Modules\Finanzas\Domain\Models\LiquidacionDescuentoDocente;
use App\Modules\Finanzas\Domain\Models\TarifaDocente;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function payrollManager(bool $closer = false): User
{
    $user = User::factory()->create();
    $user->givePermissionTo('gestionar_planilla');
    if ($closer) {
        $user->givePermissionTo('cerrar_liquidacion');
    }

    return $user;
}

function payrollAssignment(User $operator, ?Docente $teacher = null): CargaAcademica
{
    $period = PeriodoAcademico::factory()->create(['estado' => 'borrador', 'creado_por' => $operator->id]);
    $grade = Grado::create([
        'periodo_academico_id' => $period->id,
        'nombre' => 'Planilla '.fake()->unique()->numberBetween(100, 999),
        'nivel' => 'secundaria',
        'orden' => 1,
        'activo' => true,
    ]);
    $section = Seccion::create([
        'grado_id' => $grade->id,
        'nombre' => 'P'.fake()->unique()->numberBetween(100, 999),
        'turno' => 'mañana',
        'activo' => true,
    ]);

    return CargaAcademica::create([
        'seccion_id' => $section->id,
        'curso_id' => Curso::factory()->create()->id,
        'docente_id' => ($teacher ?? Docente::factory()->create())->id,
        'vigente_desde' => '2026-03-01',
        'activo' => true,
        'asignado_por' => $operator->id,
    ]);
}

function payrollSession(CargaAcademica $assignment, string $date, string $start, string $end, string $status = 'programada'): SesionClase
{
    return SesionClase::create([
        'carga_academica_id' => $assignment->id,
        'fecha' => $date,
        'hora_inicio' => $start,
        'hora_fin' => $end,
        'estado' => $status,
    ]);
}

it('creates rates with future validity and rejects overlapping tariffs', function () {
    $manager = payrollManager();
    $teacher = Docente::factory()->create();

    $this->actingAs($manager)
        ->postJson('/api/v1/teacher-payroll/rates', [
            'teacher_id' => $teacher->id,
            'hourly_rate' => '20.00',
            'effective_from' => '2026-06-01',
            'effective_until' => '2026-06-30',
        ])
        ->assertCreated()
        ->assertJsonPath('data.hourly_rate', '20.00');

    $this->actingAs($manager)
        ->postJson('/api/v1/teacher-payroll/rates', [
            'teacher_id' => $teacher->id,
            'hourly_rate' => '30.00',
            'effective_from' => '2026-06-15',
        ])
        ->assertConflict();

    $this->actingAs($manager)
        ->postJson('/api/v1/teacher-payroll/rates', [
            'teacher_id' => $teacher->id,
            'hourly_rate' => '30.00',
            'effective_from' => '2026-07-01',
        ])
        ->assertCreated();
});

it('calculates tardiness and justified and unjustified absence discount formulas', function () {
    $manager = payrollManager();
    $assignment = payrollAssignment($manager);
    $teacher = $assignment->docente;
    TarifaDocente::create([
        'docente_id' => $teacher->id,
        'tarifa_hora' => 20,
        'vigente_desde' => '2026-06-01',
        'registrado_por' => $manager->id,
    ]);
    payrollSession($assignment, '2026-06-08', '08:00:00', '11:00:00');
    payrollSession($assignment, '2026-06-09', '08:00:00', '11:00:00', 'docente_ausente');
    AsistenciaDocente::create([
        'docente_id' => $teacher->id,
        'fecha' => '2026-06-08',
        'estado' => 'falta_justificada',
        'minutos_tardanza' => 30,
        'registrado_por' => $manager->id,
    ]);

    $this->actingAs($manager)
        ->withHeader('Idempotency-Key', 'liquidation-formulas')
        ->postJson('/api/v1/teacher-payroll/liquidations', [
            'period_start' => '2026-06-01',
            'period_end' => '2026-06-30',
            'teacher_ids' => [$teacher->id],
        ])
        ->assertCreated()
        ->assertJsonPath('data.0.late_minutes', 30)
        ->assertJsonPath('data.0.late_amount', '10.00')
        ->assertJsonPath('data.0.justified_absence_hours', '3.00')
        ->assertJsonPath('data.0.justified_absence_amount', '60.00')
        ->assertJsonPath('data.0.unjustified_absence_hours', '3.00')
        ->assertJsonPath('data.0.unjustified_absence_amount', '120.00')
        ->assertJsonPath('data.0.total_discount_amount', '190.00');
});

it('keeps historical rate snapshots immutable when a future tariff changes', function () {
    $manager = payrollManager();
    $assignment = payrollAssignment($manager);
    $teacher = $assignment->docente;
    TarifaDocente::create([
        'docente_id' => $teacher->id,
        'tarifa_hora' => 20,
        'vigente_desde' => '2026-06-01',
        'vigente_hasta' => '2026-06-30',
        'registrado_por' => $manager->id,
    ]);
    payrollSession($assignment, '2026-06-09', '08:00:00', '11:00:00', 'docente_ausente');

    $this->actingAs($manager)
        ->withHeader('Idempotency-Key', 'liquidation-historical')
        ->postJson('/api/v1/teacher-payroll/liquidations', [
            'period_start' => '2026-06-01',
            'period_end' => '2026-06-30',
            'teacher_ids' => [$teacher->id],
        ])->assertCreated();

    $this->actingAs($manager)
        ->postJson('/api/v1/teacher-payroll/rates', [
            'teacher_id' => $teacher->id,
            'hourly_rate' => '50.00',
            'effective_from' => '2026-07-01',
        ])->assertCreated();

    expect(LiquidacionDescuentoDocente::firstOrFail()->tarifa_hora_snapshot)->toBe('20.00')
        ->and(LiquidacionDescuentoDocente::firstOrFail()->monto_total_descuento)->toBe('120.00');
});

it('closes liquidations transactionally and blocks recalculation after closure', function () {
    $manager = payrollManager(true);
    $assignment = payrollAssignment($manager);
    $teacher = $assignment->docente;
    TarifaDocente::create(['docente_id' => $teacher->id, 'tarifa_hora' => 20, 'vigente_desde' => '2026-06-01', 'registrado_por' => $manager->id]);
    payrollSession($assignment, '2026-06-09', '08:00:00', '11:00:00', 'docente_ausente');

    $liquidationId = $this->actingAs($manager)
        ->withHeader('Idempotency-Key', 'liquidation-close')
        ->postJson('/api/v1/teacher-payroll/liquidations', [
            'period_start' => '2026-06-01',
            'period_end' => '2026-06-30',
            'teacher_ids' => [$teacher->id],
        ])->assertCreated()->json('data.0.id');

    $this->actingAs($manager)
        ->postJson("/api/v1/teacher-payroll/liquidations/{$liquidationId}/closure")
        ->assertOk()
        ->assertJsonPath('data.status', 'cerrada');

    $this->actingAs($manager)
        ->withHeader('Idempotency-Key', 'liquidation-recalculate-closed')
        ->postJson('/api/v1/teacher-payroll/liquidations', [
            'period_start' => '2026-06-01',
            'period_end' => '2026-06-30',
            'teacher_ids' => [$teacher->id],
        ])->assertConflict();
});

it('queues payroll reports and validates authorization and errors', function () {
    $manager = payrollManager();
    $closer = payrollManager(true);
    $teacher = Docente::factory()->create();

    $this->postJson('/api/v1/teacher-payroll/liquidations', [
        'period_start' => '2026-06-01',
        'period_end' => '2026-06-30',
    ])->assertUnauthorized();

    $this->actingAs($manager)
        ->postJson('/api/v1/teacher-payroll/liquidations', [])
        ->assertUnprocessable();

    $this->actingAs($manager)
        ->withHeader('Idempotency-Key', 'payroll-report')
        ->postJson('/api/v1/teacher-payroll/reports', [
            'period_start' => '2026-06-01',
            'period_end' => '2026-06-30',
            'format' => 'xlsx',
            'teacher_ids' => [$teacher->id],
        ])->assertAccepted()
        ->assertJsonPath('data.status', 'queued');

    $this->actingAs($manager)
        ->postJson('/api/v1/teacher-payroll/liquidations/00000000-0000-7000-8000-000000000000/closure')
        ->assertForbidden();

    $this->actingAs($closer)
        ->postJson('/api/v1/teacher-payroll/liquidations/00000000-0000-7000-8000-000000000000/closure')
        ->assertNotFound();
});
