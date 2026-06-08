<?php

namespace Tests\Feature\Modules\Finanzas;

use App\Modules\Academico\Infrastructure\Models\PeriodoAcademico;
use App\Modules\Finanzas\Infrastructure\Models\BeneficioAlumno;
use App\Modules\Finanzas\Infrastructure\Models\ConceptoPago;
use App\Modules\Finanzas\Infrastructure\Models\ObligacionPago;
use App\Modules\Usuarios\Infrastructure\Models\Alumno;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentObligationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected PeriodoAcademico $period;

    protected ConceptoPago $concept;

    protected Alumno $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->admin->assignRole('administrativo');
        $this->admin->givePermissionTo('gestionar_finanzas');

        $this->period = PeriodoAcademico::factory()->create(['estado' => 'activo']);

        $this->concept = ConceptoPago::factory()->create([
            'periodo_academico_id' => $this->period->id,
            'estado' => 'vigente',
            'monto_base' => 1000,
            'descuento_pronto_pago' => 100,
        ]);

        $this->student = Alumno::factory()
            ->has(User::factory())
            ->create();
    }

    public function test_end_to_end_generate_and_adjust_obligation(): void
    {
        // 1. Generate obligation
        $generateResponse = $this->actingAs($this->admin)
            ->postJson('/api/v1/payment-obligations', [
                'academic_period_id' => $this->period->id,
                'concept_id' => $this->concept->id,
                'due_date' => now()->addMonth()->toDateString(),
                'student_ids' => [$this->student->id],
            ]);

        $generateResponse->assertAccepted();

        // 2. Get the created obligation
        $listResponse = $this->actingAs($this->admin)
            ->getJson('/api/v1/payment-obligations');

        $listResponse->assertOk();
        $obligations = $listResponse->json('data');
        $this->assertCount(1, $obligations);
        $obligationId = $obligations[0]['id'];

        // 3. Show obligation detail
        $showResponse = $this->actingAs($this->admin)
            ->getJson("/api/v1/payment-obligations/{$obligationId}");

        $showResponse->assertOk();
        $obligation = $showResponse->json('data');
        $this->assertEquals('pendiente', $obligation['status']);
        $this->assertEquals(1000, $obligation['amounts']['ordinary']);
        $this->assertEquals(900, $obligation['amounts']['early_payment']);

        // 4. Apply discount adjustment
        $adjustResponse = $this->actingAs($this->admin)
            ->postJson(
                "/api/v1/payment-obligations/{$obligationId}/adjustments",
                [
                    'adjustment_type' => 'discount',
                    'amount' => 100,
                    'reason' => 'Descuento por buen desempeño',
                ]
            );

        $adjustResponse->assertCreated();

        // 5. Verify adjustment was applied
        $showResponse = $this->actingAs($this->admin)
            ->getJson("/api/v1/payment-obligations/{$obligationId}");

        $showResponse->assertOk();
        $adjustedObligation = $showResponse->json('data');
        $this->assertEquals(900, $adjustedObligation['amounts']['ordinary']);
        $this->assertEquals(800, $adjustedObligation['amounts']['early_payment']);
    }

    public function test_generate_obligation_with_student_benefit(): void
    {
        // Create a benefit for the student
        $benefit = BeneficioAlumno::factory()->create([
            'alumno_id' => $this->student->id,
            'monto_descuento' => 200,
            'estado' => 'activo',
        ]);

        // Generate obligation (should apply benefit)
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/payment-obligations', [
                'academic_period_id' => $this->period->id,
                'concept_id' => $this->concept->id,
                'due_date' => now()->addMonth()->toDateString(),
                'student_ids' => [$this->student->id],
            ]);

        $response->assertAccepted();

        // Verify obligation has benefit reference
        $this->assertDatabaseHas('obligaciones_pago', [
            'alumno_id' => $this->student->id,
            'concepto_id' => $this->concept->id,
            'beneficio_id' => $benefit->id,
            'monto_ordinario_snapshot' => 800, // 1000 - 200 benefit
        ]);
    }

    public function test_generate_obligations_for_multiple_students(): void
    {
        $student2 = Alumno::factory()->has(User::factory())->create();
        $student3 = Alumno::factory()->has(User::factory())->create();

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/payment-obligations', [
                'academic_period_id' => $this->period->id,
                'concept_id' => $this->concept->id,
                'due_date' => now()->addMonth()->toDateString(),
                'student_ids' => [$this->student->id, $student2->id, $student3->id],
            ]);

        $response->assertAccepted();

        // Verify all obligations were created
        $this->assertDatabaseHas('obligaciones_pago', [
            'alumno_id' => $this->student->id,
            'concepto_id' => $this->concept->id,
        ]);

        $this->assertDatabaseHas('obligaciones_pago', [
            'alumno_id' => $student2->id,
            'concepto_id' => $this->concept->id,
        ]);

        $this->assertDatabaseHas('obligaciones_pago', [
            'alumno_id' => $student3->id,
            'concepto_id' => $this->concept->id,
        ]);
    }

    public function test_list_obligations_with_filters(): void
    {
        // Create multiple obligations with different states
        ObligacionPago::factory(2)->create([
            'alumno_id' => $this->student->id,
            'concepto_id' => $this->concept->id,
            'estado' => 'pendiente',
        ]);

        ObligacionPago::factory()->create([
            'alumno_id' => $this->student->id,
            'concepto_id' => $this->concept->id,
            'estado' => 'pagado',
        ]);

        // List pending obligations
        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/payment-obligations?estado=pendiente');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));

        // List paid obligations
        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/payment-obligations?estado=pagado');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_snapshot_values_remain_frozen_after_concept_update(): void
    {
        // Generate obligation with original values
        $this->actingAs($this->admin)
            ->postJson('/api/v1/payment-obligations', [
                'academic_period_id' => $this->period->id,
                'concept_id' => $this->concept->id,
                'due_date' => now()->addMonth()->toDateString(),
                'student_ids' => [$this->student->id],
            ]);

        // Get the obligation before concept update
        $obligation = ObligacionPago::where('alumno_id', $this->student->id)
            ->where('concepto_id', $this->concept->id)
            ->first();

        $originalMonto = $obligation->monto_ordinario_snapshot;

        // Update concept (should not affect existing obligations)
        $this->concept->update(['monto_base' => 2000]);

        $obligation->refresh();

        // Verify snapshot remained unchanged
        $this->assertEquals($originalMonto, $obligation->monto_ordinario_snapshot);
        $this->assertEquals(1000, $originalMonto);
    }
}
