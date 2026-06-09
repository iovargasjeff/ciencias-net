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

class PaymentMovementReversalRefundTest extends TestCase
{
    use RefreshDatabase;

    protected User $yanina;

    protected ObligacionPago $obligation;

    protected MovimientoPago $originalPayment;

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
            'estado' => 'pagado',
            'monto_cobrado' => 450.00,
            'fecha_pago' => now(),
        ]);

        $this->originalPayment = MovimientoPago::create([
            'obligacion_pago_id' => $this->obligation->id,
            'tipo' => 'pago',
            'monto' => 450.00,
            'medio_pago' => 'efectivo',
            'numero_recibo' => 'REC-2026-00001',
            'registrado_por' => $this->yanina->id,
        ]);
    }

    public function test_reverses_payment_and_restores_obligation(): void
    {
        $response = $this->actingAs($this->yanina)
            ->postJson('/api/v1/payment-movements', [
                'obligation_id' => $this->obligation->id,
                'movement_type' => 'reversal',
                'amount' => '450.00',
                'occurred_at' => now()->format('Y-m-d\TH:i:s'),
                'reason' => 'Pago aplicado a obligación incorrecta',
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.movement_type', 'anulacion');

        $this->assertDatabaseHas('movimientos_pago', [
            'obligacion_pago_id' => $this->obligation->id,
            'tipo' => 'anulacion',
            'monto' => 450.00,
        ]);

        $this->assertDatabaseHas('obligaciones_pago', [
            'id' => $this->obligation->id,
            'estado' => 'pendiente',
            'monto_cobrado' => null,
        ]);
    }

    public function test_reversal_preserves_original_payment_record(): void
    {
        $this->actingAs($this->yanina)
            ->postJson('/api/v1/payment-movements', [
                'obligation_id' => $this->obligation->id,
                'movement_type' => 'reversal',
                'amount' => '450.00',
                'occurred_at' => now()->format('Y-m-d\TH:i:s'),
                'reason' => 'Error administrativo',
            ]);

        $this->assertDatabaseHas('movimientos_pago', [
            'id' => $this->originalPayment->id,
            'tipo' => 'pago',
            'monto' => 450.00,
        ]);
    }

    public function test_requires_reason_for_reversal(): void
    {
        $response = $this->actingAs($this->yanina)
            ->postJson('/api/v1/payment-movements', [
                'obligation_id' => $this->obligation->id,
                'movement_type' => 'reversal',
                'amount' => '450.00',
                'occurred_at' => now()->format('Y-m-d\TH:i:s'),
            ]);

        $response->assertUnprocessable();
    }

    public function test_registers_refund_for_payment(): void
    {
        $response = $this->actingAs($this->yanina)
            ->postJson('/api/v1/payment-movements', [
                'obligation_id' => $this->obligation->id,
                'movement_type' => 'refund',
                'amount' => '100.00',
                'occurred_at' => now()->format('Y-m-d\TH:i:s'),
                'reason' => 'Devolución parcial por ajuste',
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.movement_type', 'devolucion');

        $this->assertDatabaseHas('movimientos_pago', [
            'obligacion_pago_id' => $this->obligation->id,
            'tipo' => 'devolucion',
            'monto' => 100.00,
        ]);
    }

    public function test_refund_does_not_exceed_original_amount(): void
    {
        $response = $this->actingAs($this->yanina)
            ->postJson('/api/v1/payment-movements', [
                'obligation_id' => $this->obligation->id,
                'movement_type' => 'refund',
                'amount' => '9999.00',
                'occurred_at' => now()->format('Y-m-d\TH:i:s'),
                'reason' => 'Devolución excesiva',
            ]);

        $response->assertStatus(409);
    }
}
