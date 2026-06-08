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

class PaymentMovementRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $yanina;

    protected PeriodoAcademico $period;

    protected ConceptoPago $concept;

    protected Alumno $student;

    protected ObligacionPago $obligation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->yanina = User::factory()->create();
        $this->yanina->assignRole('administrativo');
        $this->yanina->givePermissionTo('gestionar_finanzas');

        $this->period = PeriodoAcademico::factory()->create(['estado' => 'activo']);
        $this->concept = ConceptoPago::factory()->create([
            'periodo_academico_id' => $this->period->id,
            'estado' => 'vigente',
            'monto_base' => 500,
            'descuento_pronto_pago' => 50,
            'fecha_limite_pronto_pago' => now()->addDays(15),
            'creado_por' => $this->yanina->id,
        ]);

        $this->student = Alumno::factory()
            ->has(User::factory())
            ->create();

        $grade = Grado::create([
            'periodo_academico_id' => $this->period->id,
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
            'alumno_id' => $this->student->id,
            'seccion_id' => $section->id,
            'codigo' => 'MAT-'.fake()->unique()->numerify('######'),
            'fecha' => now()->toDateString(),
            'estado' => 'activo',
            'registrado_por' => $this->yanina->id,
        ]);

        $this->obligation = ObligacionPago::factory()->create([
            'alumno_id' => $this->student->id,
            'concepto_id' => $this->concept->id,
            'monto_base_snapshot' => 500,
            'monto_ordinario_snapshot' => 500,
            'monto_pronto_pago_snapshot' => 450,
            'descuento_pronto_pago_aplicado' => 50,
            'fecha_limite_pronto_pago_snapshot' => now()->addDays(15),
            'estado' => 'pendiente',
        ]);
    }

    public function test_registers_payment_with_exact_amount(): void
    {
        $response = $this->actingAs($this->yanina)
            ->postJson('/api/v1/payment-movements', [
                'obligation_id' => $this->obligation->id,
                'movement_type' => 'payment',
                'amount' => '450.00',
                'occurred_at' => now()->format('Y-m-d\TH:i:s'),
                'method' => 'cash',
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.movement_type', 'pago');
        $response->assertJsonPath('data.amount', 450);

        $this->assertDatabaseHas('movimientos_pago', [
            'obligacion_pago_id' => $this->obligation->id,
            'tipo' => 'pago',
            'monto' => 450.00,
        ]);

        $this->assertDatabaseHas('obligaciones_pago', [
            'id' => $this->obligation->id,
            'estado' => 'pagado',
            'monto_cobrado' => 450.00,
        ]);
    }

    public function test_rejects_payment_with_wrong_amount(): void
    {
        $response = $this->actingAs($this->yanina)
            ->postJson('/api/v1/payment-movements', [
                'obligation_id' => $this->obligation->id,
                'movement_type' => 'payment',
                'amount' => '400.00',
                'occurred_at' => now()->format('Y-m-d\TH:i:s'),
                'method' => 'cash',
            ]);

        $response->assertStatus(409);
        $this->assertDatabaseMissing('movimientos_pago', [
            'obligacion_pago_id' => $this->obligation->id,
        ]);
    }

    public function test_uses_ordinary_amount_after_early_payment_deadline(): void
    {
        $response = $this->actingAs($this->yanina)
            ->postJson('/api/v1/payment-movements', [
                'obligation_id' => $this->obligation->id,
                'movement_type' => 'payment',
                'amount' => '500.00',
                'occurred_at' => now()->addDays(20)->format('Y-m-d\TH:i:s'),
                'method' => 'transfer',
                'reference' => 'TXN-12345',
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.amount', 500);
    }

    public function test_rejects_payment_for_already_paid_obligation(): void
    {
        $this->obligation->update([
            'estado' => 'pagado',
            'monto_cobrado' => 450,
            'fecha_pago' => now(),
        ]);

        $response = $this->actingAs($this->yanina)
            ->postJson('/api/v1/payment-movements', [
                'obligation_id' => $this->obligation->id,
                'movement_type' => 'payment',
                'amount' => '450.00',
                'occurred_at' => now()->format('Y-m-d\TH:i:s'),
                'method' => 'cash',
            ]);

        $response->assertStatus(409);
    }

    public function test_rejects_duplicate_reference(): void
    {
        MovimientoPago::create([
            'obligacion_pago_id' => $this->obligation->id,
            'tipo' => 'pago',
            'monto' => 450.00,
            'medio_pago' => 'transferencia',
            'referencia' => 'TXN-99999',
            'numero_recibo' => 'REC-2026-00001',
            'registrado_por' => $this->yanina->id,
        ]);

        $this->obligation->update([
            'estado' => 'pagado',
            'monto_cobrado' => 450,
            'fecha_pago' => now(),
        ]);

        $anotherObligation = ObligacionPago::factory()->create([
            'alumno_id' => $this->student->id,
            'concepto_id' => $this->concept->id,
            'monto_base_snapshot' => 500,
            'monto_ordinario_snapshot' => 500,
            'monto_pronto_pago_snapshot' => 450,
            'descuento_pronto_pago_aplicado' => 50,
            'estado' => 'pendiente',
        ]);

        $response = $this->actingAs($this->yanina)
            ->postJson('/api/v1/payment-movements', [
                'obligation_id' => $anotherObligation->id,
                'movement_type' => 'payment',
                'amount' => '450.00',
                'occurred_at' => now()->format('Y-m-d\TH:i:s'),
                'method' => 'transfer',
                'reference' => 'TXN-99999',
            ]);

        $response->assertStatus(409);
    }

    public function test_generates_sequential_receipt_numbers(): void
    {
        $first = $this->actingAs($this->yanina)
            ->postJson('/api/v1/payment-movements', [
                'obligation_id' => $this->obligation->id,
                'movement_type' => 'payment',
                'amount' => '450.00',
                'occurred_at' => now()->format('Y-m-d\TH:i:s'),
                'method' => 'cash',
            ]);

        $first->assertStatus(201);
        $firstReceipt = $first->json('data.receipt_number');

        $anotherObligation = ObligacionPago::factory()->create([
            'alumno_id' => $this->student->id,
            'concepto_id' => $this->concept->id,
            'monto_base_snapshot' => 500,
            'monto_ordinario_snapshot' => 500,
            'monto_pronto_pago_snapshot' => 450,
            'descuento_pronto_pago_aplicado' => 50,
            'estado' => 'pendiente',
        ]);

        $second = $this->actingAs($this->yanina)
            ->postJson('/api/v1/payment-movements', [
                'obligation_id' => $anotherObligation->id,
                'movement_type' => 'payment',
                'amount' => '450.00',
                'occurred_at' => now()->format('Y-m-d\TH:i:s'),
                'method' => 'yape',
                'reference' => 'YAPE-54321',
            ]);

        $second->assertStatus(201);
        $secondReceipt = $second->json('data.receipt_number');

        $this->assertNotEquals($firstReceipt, $secondReceipt);
        $this->assertStringStartsWith('REC-'.now()->format('Y').'-', $firstReceipt);
        $this->assertStringStartsWith('REC-'.now()->format('Y').'-', $secondReceipt);
    }
}
