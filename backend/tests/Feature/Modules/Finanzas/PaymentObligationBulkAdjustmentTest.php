<?php

namespace Tests\Feature\Modules\Finanzas;

use App\Modules\Finanzas\Infrastructure\Models\ObligacionPago;
use App\Modules\Usuarios\Infrastructure\Models\Alumno;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentObligationBulkAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $yanina;

    protected User $unauthorized;

    protected array $obligations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        // Create Yanina user with gestionar_finanzas permission
        $this->yanina = User::factory()->create();
        $this->yanina->assignRole('administrativo');
        $this->yanina->givePermissionTo('gestionar_finanzas');

        // Create unauthorized user
        $this->unauthorized = User::factory()->create();

        // Create students
        $student1 = Alumno::factory()->has(User::factory())->create();
        $student2 = Alumno::factory()->has(User::factory())->create();
        $student3 = Alumno::factory()->has(User::factory())->create();

        // Create pending obligations
        $this->obligations = [];
        foreach ([$student1, $student2, $student3] as $student) {
            $this->obligations[] = ObligacionPago::factory()->create([
                'alumno_id' => $student->id,
                'estado' => 'pendiente',
                'monto_base_snapshot' => 500,
                'monto_ordinario_snapshot' => 500,
                'monto_pronto_pago_snapshot' => 500,
            ]);
        }
    }

    public function test_bulk_adjust_multiple_obligations(): void
    {
        $obligationIds = array_map(fn ($o) => $o->id, $this->obligations);

        $response = $this->actingAs($this->yanina)
            ->postJson('/api/v1/payment-obligations/bulk-adjustments', [
                'filters' => ['obligation_ids' => $obligationIds],
                'adjustment_type' => 'discount',
                'amount' => 50,
                'reason' => 'Descuento por beca aprobada para múltiples estudiantes',
            ]);

        $response->assertAccepted();
        $response->assertJsonPath('data.count_affected', 3);
        $response->assertJsonPath('data.status', 'completed');

        foreach ($this->obligations as $obligation) {
            $obligation->refresh();
            $this->assertEquals(450, $obligation->monto_ordinario_snapshot);
        }
    }

    public function test_bulk_adjust_with_partial_failures(): void
    {
        // Create a paid obligation that should fail
        $paidObligation = ObligacionPago::factory()->create([
            'estado' => 'pagado',
            'monto_base_snapshot' => 500,
            'monto_ordinario_snapshot' => 500,
            'monto_pronto_pago_snapshot' => 500,
            'monto_cobrado' => 500,
            'fecha_pago' => now(),
        ]);

        $obligationIds = array_merge(
            array_map(fn ($o) => $o->id, $this->obligations),
            [$paidObligation->id]
        );

        $response = $this->actingAs($this->yanina)
            ->postJson('/api/v1/payment-obligations/bulk-adjustments', [
                'filters' => ['obligation_ids' => $obligationIds],
                'adjustment_type' => 'discount',
                'amount' => 50,
                'reason' => 'Bulk adjustment with mixed statuses',
            ]);

        $response->assertAccepted();
        $response->assertJsonPath('data.count_affected', 3);
        $response->assertJsonPath('data.status', 'completed');

        // Verify pending obligations were adjusted
        foreach ($this->obligations as $obligation) {
            $obligation->refresh();
            $this->assertEquals(450, $obligation->monto_ordinario_snapshot);
        }

        // Verify paid obligation was not adjusted
        $paidObligation->refresh();
        $this->assertEquals(500, $paidObligation->monto_ordinario_snapshot);
    }

    public function test_bulk_adjust_without_permission_returns_403(): void
    {
        $obligationIds = array_map(fn ($o) => $o->id, $this->obligations);

        $response = $this->actingAs($this->unauthorized)
            ->postJson('/api/v1/payment-obligations/bulk-adjustments', [
                'filters' => ['obligation_ids' => $obligationIds],
                'adjustment_type' => 'discount',
                'amount' => 50,
                'reason' => 'Test',
            ]);

        $response->assertForbidden();
    }

    public function test_bulk_adjust_with_invalid_adjustment_type(): void
    {
        $obligationIds = array_map(fn ($o) => $o->id, $this->obligations);

        $response = $this->actingAs($this->yanina)
            ->postJson('/api/v1/payment-obligations/bulk-adjustments', [
                'filters' => ['obligation_ids' => $obligationIds],
                'adjustment_type' => 'invalid_type',
                'amount' => 50,
                'reason' => 'Test',
            ]);

        $response->assertUnprocessable();
        $response->assertJsonStructure(['error' => ['fields' => ['adjustment_type']]]);
    }

    public function test_bulk_adjust_returns_failure_details(): void
    {
        $paidObligation = ObligacionPago::factory()->create([
            'estado' => 'pagado',
            'monto_base_snapshot' => 500,
            'monto_ordinario_snapshot' => 500,
            'monto_pronto_pago_snapshot' => 500,
            'monto_cobrado' => 500,
            'fecha_pago' => now(),
        ]);

        $obligationIds = array_merge(
            array_map(fn ($o) => $o->id, $this->obligations),
            [$paidObligation->id]
        );

        $response = $this->actingAs($this->yanina)
            ->postJson('/api/v1/payment-obligations/bulk-adjustments', [
                'filters' => ['obligation_ids' => $obligationIds],
                'adjustment_type' => 'discount',
                'amount' => 50,
                'reason' => 'Test failure details',
            ]);

        $response->assertAccepted();
        $response->assertJsonPath('data.count_affected', 3);
        $response->assertJsonPath('data.status', 'completed');
    }

    public function test_bulk_adjust_requires_reason(): void
    {
        $obligationIds = array_map(fn ($o) => $o->id, $this->obligations);

        $response = $this->actingAs($this->yanina)
            ->postJson('/api/v1/payment-obligations/bulk-adjustments', [
                'filters' => ['obligation_ids' => $obligationIds],
                'adjustment_type' => 'discount',
                'amount' => 50,
                // missing 'reason'
            ]);

        $response->assertUnprocessable();
        $response->assertJsonStructure(['error' => ['fields' => ['reason']]]);
    }
}
