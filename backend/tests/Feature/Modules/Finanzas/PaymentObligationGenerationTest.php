<?php

namespace Tests\Feature\Modules\Finanzas;

use App\Modules\Academico\Infrastructure\Models\PeriodoAcademico;
use App\Modules\Finanzas\Infrastructure\Models\ConceptoPago;
use App\Modules\Finanzas\Infrastructure\Models\ObligacionPago;
use App\Modules\Usuarios\Infrastructure\Models\Alumno;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentObligationGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected User $yanina;

    protected User $unauthorized;

    protected PeriodoAcademico $period;

    protected ConceptoPago $concept;

    protected Alumno $student;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Yanina user with gestionar_finanzas permission
        $this->yanina = User::factory()->create();
        $this->yanina->assignRole('administrativo');
        $this->yanina->givePermissionTo('gestionar_finanzas');

        // Create unauthorized user
        $this->unauthorized = User::factory()->create();

        // Create academic period
        $this->period = PeriodoAcademico::factory()->create(['estado' => 'activo']);

        // Create concept
        $this->concept = ConceptoPago::factory()->create([
            'periodo_academico_id' => $this->period->id,
            'estado' => 'vigente',
            'monto_base' => 500,
            'descuento_pronto_pago' => 50,
            'creado_por' => $this->yanina->id,
        ]);

        // Create enrolled student
        $this->student = Alumno::factory()
            ->has(User::factory())
            ->create();
    }

    public function test_generate_obligation_creates_with_frozen_snapshots(): void
    {
        $response = $this->actingAs($this->yanina)
            ->postJson('/api/v1/payment-obligations', [
                'academic_period_id' => $this->period->id,
                'concept_id' => $this->concept->id,
                'due_date' => now()->addMonth()->toDateString(),
                'student_ids' => [$this->student->id],
            ]);

        $response->assertStatus(202);

        $this->assertDatabaseHas('obligaciones_pago', [
            'alumno_id' => $this->student->id,
            'concepto_id' => $this->concept->id,
            'estado' => 'pendiente',
            'monto_base_snapshot' => 500,
            'monto_ordinario_snapshot' => 500,
            'monto_pronto_pago_snapshot' => 450,
            'descuento_pronto_pago_aplicado' => 50,
        ]);
    }

    public function test_generate_obligation_without_permission_returns_403(): void
    {
        $response = $this->actingAs($this->unauthorized)
            ->postJson('/api/v1/payment-obligations', [
                'academic_period_id' => $this->period->id,
                'concept_id' => $this->concept->id,
                'due_date' => now()->addMonth()->toDateString(),
            ]);

        $response->assertForbidden();
    }

    public function test_generate_obligation_with_invalid_concept_returns_409(): void
    {
        $response = $this->actingAs($this->yanina)
            ->postJson('/api/v1/payment-obligations', [
                'academic_period_id' => $this->period->id,
                'concept_id' => 'invalid-uuid-format',
                'due_date' => now()->addMonth()->toDateString(),
            ]);

        $response->assertUnprocessable();
    }

    public function test_generate_obligation_with_past_due_date_returns_422(): void
    {
        $response = $this->actingAs($this->yanina)
            ->postJson('/api/v1/payment-obligations', [
                'academic_period_id' => $this->period->id,
                'concept_id' => $this->concept->id,
                'due_date' => now()->subDay()->toDateString(),
            ]);

        $response->assertUnprocessable();
    }

    public function test_list_obligations_returns_paginated_results(): void
    {
        // Create test obligations
        ObligacionPago::factory(3)->create([
            'alumno_id' => $this->student->id,
            'concepto_id' => $this->concept->id,
        ]);

        $response = $this->actingAs($this->yanina)
            ->getJson('/api/v1/payment-obligations');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    public function test_list_obligations_filters_by_estado(): void
    {
        ObligacionPago::factory()->create([
            'alumno_id' => $this->student->id,
            'concepto_id' => $this->concept->id,
            'estado' => 'pendiente',
        ]);

        ObligacionPago::factory()->create([
            'alumno_id' => $this->student->id,
            'concepto_id' => $this->concept->id,
            'estado' => 'pagado',
        ]);

        $response = $this->actingAs($this->yanina)
            ->getJson('/api/v1/payment-obligations?estado=pendiente');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    public function test_show_obligation_returns_detail(): void
    {
        $obligation = ObligacionPago::factory()->create([
            'alumno_id' => $this->student->id,
            'concepto_id' => $this->concept->id,
        ]);

        $response = $this->actingAs($this->yanina)
            ->getJson("/api/v1/payment-obligations/{$obligation->id}");

        $response->assertOk();
        $response->assertJsonPath('data.id', $obligation->id);
        $response->assertJsonPath('data.status', 'pendiente');
    }
}
