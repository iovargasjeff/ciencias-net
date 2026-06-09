<?php

namespace Tests\Feature\Modules\Finanzas;

use App\Modules\Academico\Infrastructure\Models\Grado;
use App\Modules\Academico\Infrastructure\Models\Matricula;
use App\Modules\Academico\Infrastructure\Models\PeriodoAcademico;
use App\Modules\Academico\Infrastructure\Models\Seccion;
use App\Modules\Finanzas\Infrastructure\Models\ConceptoPago;
use App\Modules\Finanzas\Infrastructure\Models\MovimientoPago;
use App\Modules\Finanzas\Infrastructure\Models\ObligacionPago;
use App\Modules\Usuarios\Infrastructure\Models\Alumno;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentMovementIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $yanina;

    protected ObligacionPago $obligation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->yanina = User::factory()->create();
        $this->yanina->assignRole('administrativo');
        $this->yanina->givePermissionTo('gestionar_finanzas');

        $period = PeriodoAcademico::factory()->create(['estado' => 'activo']);
        $concept = ConceptoPago::factory()->create([
            'periodo_academico_id' => $period->id,
            'estado' => 'vigente',
            'monto_base' => 500,
            'descuento_pronto_pago' => 50,
            'fecha_limite_pronto_pago' => now()->addDays(15),
            'creado_por' => $this->yanina->id,
        ]);

        $student = Alumno::factory()
            ->has(User::factory())
            ->create();

        $grade = Grado::create([
            'periodo_academico_id' => $period->id,
            'nombre' => 'Primer Grado',
            'nivel' => 'primaria',
            'orden' => 1,
            'activo' => true,
        ]);
        $section = Seccion::create([
            'grado_id' => $grade->id,
            'nombre' => 'A',
            'turno' => 'manana',
            'aula' => '101',
            'capacidad' => 30,
            'activo' => true,
        ]);
        Matricula::create([
            'alumno_id' => $student->id,
            'seccion_id' => $section->id,
            'codigo' => 'MAT-'.fake()->unique()->numerify('######'),
            'fecha' => now()->toDateString(),
            'estado' => 'activo',
            'registrado_por' => $this->yanina->id,
        ]);

        $this->obligation = ObligacionPago::factory()->create([
            'alumno_id' => $student->id,
            'concepto_id' => $concept->id,
            'monto_base_snapshot' => 500,
            'monto_ordinario_snapshot' => 500,
            'monto_pronto_pago_snapshot' => 450,
            'descuento_pronto_pago_aplicado' => 50,
            'fecha_limite_pronto_pago_snapshot' => now()->addDays(15),
            'estado' => 'pendiente',
        ]);
    }

    public function test_end_to_end_pay_and_reverse(): void
    {
        $payResponse = $this->actingAs($this->yanina)
            ->postJson('/api/v1/payment-movements', [
                'obligation_id' => $this->obligation->id,
                'movement_type' => 'payment',
                'amount' => '450.00',
                'occurred_at' => now()->format('Y-m-d\TH:i:s'),
                'method' => 'transfer',
                'reference' => 'TXN-99999',
            ]);

        $payResponse->assertStatus(201);
        $payReceipt = $payResponse->json('data.receipt_number');

        $this->assertDatabaseHas('obligaciones_pago', [
            'id' => $this->obligation->id,
            'estado' => 'pagado',
        ]);

        $reverseResponse = $this->actingAs($this->yanina)
            ->postJson('/api/v1/payment-movements', [
                'obligation_id' => $this->obligation->id,
                'movement_type' => 'reversal',
                'amount' => '450.00',
                'occurred_at' => now()->format('Y-m-d\TH:i:s'),
                'reason' => 'Pago duplicado',
            ]);

        $reverseResponse->assertStatus(201);

        $this->assertDatabaseHas('obligaciones_pago', [
            'id' => $this->obligation->id,
            'estado' => 'pendiente',
            'monto_cobrado' => null,
        ]);

        $movements = MovimientoPago::query()
            ->where('obligacion_pago_id', $this->obligation->id)
            ->count();

        $this->assertEquals(2, $movements);
    }

    public function test_receipt_endpoint_returns_movement_info(): void
    {
        $payResponse = $this->actingAs($this->yanina)
            ->postJson('/api/v1/payment-movements', [
                'obligation_id' => $this->obligation->id,
                'movement_type' => 'payment',
                'amount' => '450.00',
                'occurred_at' => now()->format('Y-m-d\TH:i:s'),
                'method' => 'cash',
            ]);

        $movementId = $payResponse->json('data.id');

        $receiptResponse = $this->actingAs($this->yanina)
            ->getJson("/api/v1/payment-movements/{$movementId}/receipt");

        $receiptResponse->assertOk();
        $receiptResponse->assertJsonPath('data.receipt_number', $payResponse->json('data.receipt_number'));
        $receiptResponse->assertJsonPath('data.amount', 450);
    }

    public function test_snapshots_remain_frozen_after_payment(): void
    {
        $this->actingAs($this->yanina)
            ->postJson('/api/v1/payment-movements', [
                'obligation_id' => $this->obligation->id,
                'movement_type' => 'payment',
                'amount' => '450.00',
                'occurred_at' => now()->format('Y-m-d\TH:i:s'),
                'method' => 'cash',
            ]);

        $obligation = ObligacionPago::find($this->obligation->id);

        $this->assertEquals(500, (float) $obligation->monto_base_snapshot);
        $this->assertEquals(450, (float) $obligation->monto_pronto_pago_snapshot);
    }
}
