<?php

namespace Tests\Feature\Modules\Finanzas;

use App\Modules\Academico\Infrastructure\Models\PeriodoAcademico;
use App\Modules\Finanzas\Infrastructure\Models\ConceptoPago;
use App\Modules\Finanzas\Infrastructure\Models\ObligacionPago;
use App\Modules\Usuarios\Infrastructure\Models\Alumno;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentObligationAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $yanina;

    protected User $unauthorized;

    protected ObligacionPago $pendingObligation;

    protected ObligacionPago $paidObligation;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Yanina user with gestionar_finanzas permission
        $this->yanina = User::factory()->create();
        $this->yanina->assignRole('administrativo');
        $this->yanina->givePermissionTo('gestionar_finanzas');

        // Create unauthorized user
        $this->unauthorized = User::factory()->create();

        // Create student
        $student = Alumno::factory()->has(User::factory())->create();

        // Create concept and period
        $period = PeriodoAcademico::factory()->create(['estado' => 'activo']);
        $concept = ConceptoPago::factory()->create([
            'periodo_academico_id' => $period->id,
            'estado' => 'vigente',
            'monto_base' => 500,
        ]);

        // Create pending obligation
        $this->pendingObligation = ObligacionPago::factory()->create([
            'alumno_id' => $student->id,
            'concepto_id' => $concept->id,
            'estado' => 'pendiente',
            'monto_ordinario_snapshot' => 500,
            'monto_pronto_pago_snapshot' => 450,
            'registrado_por' => $this->yanina->id,
        ]);

        // Create paid obligation
        $this->paidObligation = ObligacionPago::factory()->create([
            'alumno_id' => $student->id,
            'concepto_id' => $concept->id,
            'estado' => 'pagado',
            'monto_ordinario_snapshot' => 500,
            'monto_cobrado' => 500,
            'registrado_por' => $this->yanina->id,
        ]);
    }

    public function test_adjust_pending_obligation_with_discount(): void
    {
        $response = $this->actingAs($this->yanina)
            ->postJson(
                "/api/v1/payment-obligations/{$this->pendingObligation->id}/adjustments",
                [
                    'adjustment_type' => 'discount',
                    'amount' => 50,
                    'reason' => 'Descuento por beca aprobada',
                ]
            );

        $response->assertCreated();
        $response->assertJsonPath('data.amounts.ordinary', 450);

        $this->pendingObligation->refresh();
        $this->assertEquals(450, $this->pendingObligation->monto_ordinario_snapshot);
    }

    public function test_adjust_pending_obligation_with_charge(): void
    {
        $response = $this->actingAs($this->yanina)
            ->postJson(
                "/api/v1/payment-obligations/{$this->pendingObligation->id}/adjustments",
                [
                    'adjustment_type' => 'charge',
                    'amount' => 100,
                    'reason' => 'Cobro adicional por servicio',
                ]
            );

        $response->assertCreated();
        $response->assertJsonPath('data.amounts.ordinary', 600);
    }

    public function test_adjust_pending_obligation_with_waiver(): void
    {
        $response = $this->actingAs($this->yanina)
            ->postJson(
                "/api/v1/payment-obligations/{$this->pendingObligation->id}/adjustments",
                [
                    'adjustment_type' => 'waiver',
                    'amount' => 0,
                    'reason' => 'Condonación total por beca completa',
                ]
            );

        $response->assertCreated();
        $response->assertJsonPath('data.amounts.ordinary', 0);
    }

    public function test_adjust_paid_obligation_returns_409(): void
    {
        $response = $this->actingAs($this->yanina)
            ->postJson(
                "/api/v1/payment-obligations/{$this->paidObligation->id}/adjustments",
                [
                    'adjustment_type' => 'discount',
                    'amount' => 50,
                    'reason' => 'Invalid adjustment',
                ]
            );

        $response->assertConflict();
    }

    public function test_adjust_without_permission_returns_403(): void
    {
        $response = $this->actingAs($this->unauthorized)
            ->postJson(
                "/api/v1/payment-obligations/{$this->pendingObligation->id}/adjustments",
                [
                    'adjustment_type' => 'discount',
                    'amount' => 50,
                    'reason' => 'Test',
                ]
            );

        $response->assertForbidden();
    }

    public function test_adjust_requires_reason(): void
    {
        $response = $this->actingAs($this->yanina)
            ->postJson(
                "/api/v1/payment-obligations/{$this->pendingObligation->id}/adjustments",
                [
                    'adjustment_type' => 'discount',
                    'amount' => 50,
                    // missing 'reason'
                ]
            );

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('reason');
    }

    public function test_adjust_records_audit_trail(): void
    {
        $this->actingAs($this->yanina)
            ->postJson(
                "/api/v1/payment-obligations/{$this->pendingObligation->id}/adjustments",
                [
                    'adjustment_type' => 'discount',
                    'amount' => 100,
                    'reason' => 'Test adjustment for audit',
                ]
            );

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'finance.obligation_adjusted',
            'user_id' => $this->yanina->id,
        ]);
    }

    public function test_adjust_updates_motivo_ultima_modificacion(): void
    {
        $reason = 'Descuento por rendimiento académico';

        $this->actingAs($this->yanina)
            ->postJson(
                "/api/v1/payment-obligations/{$this->pendingObligation->id}/adjustments",
                [
                    'adjustment_type' => 'discount',
                    'amount' => 50,
                    'reason' => $reason,
                ]
            );

        $this->pendingObligation->refresh();
        $this->assertEquals($reason, $this->pendingObligation->motivo_ultima_modificacion);
    }
}
