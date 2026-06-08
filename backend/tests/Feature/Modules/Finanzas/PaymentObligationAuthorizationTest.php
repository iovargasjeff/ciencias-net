<?php

namespace Tests\Feature\Modules\Finanzas;

use App\Modules\Academico\Infrastructure\Models\PeriodoAcademico;
use App\Modules\Finanzas\Infrastructure\Models\ConceptoPago;
use App\Modules\Finanzas\Infrastructure\Models\ObligacionPago;
use App\Modules\Usuarios\Infrastructure\Models\Alumno;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentObligationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $teacher;

    protected User $student;

    protected User $unauthorized;

    protected ObligacionPago $obligation;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users with different roles
        $this->admin = User::factory()->create();
        $this->admin->assignRole('administrativo');
        $this->admin->givePermissionTo('gestionar_finanzas');

        $this->teacher = User::factory()->create();
        $this->teacher->assignRole('docente');

        $this->student = User::factory()->create();
        $this->student->assignRole('estudiante');

        $this->unauthorized = User::factory()->create();

        // Create test obligation
        $alumno = Alumno::factory()->has(User::factory())->create();
        $period = PeriodoAcademico::factory()->create();
        $concept = ConceptoPago::factory()->create([
            'periodo_academico_id' => $period->id,
        ]);

        $this->obligation = ObligacionPago::factory()->create([
            'alumno_id' => $alumno->id,
            'concepto_id' => $concept->id,
            'estado' => 'pendiente',
        ]);
    }

    public function test_generate_obligations_requires_gestionar_finanzas_permission(): void
    {
        $response = $this->actingAs($this->teacher)
            ->postJson('/api/v1/payment-obligations', [
                'academic_period_id' => 'period-id',
                'concept_id' => 'concept-id',
                'due_date' => now()->addMonth()->toDateString(),
            ]);

        $response->assertForbidden();
    }

    public function test_list_obligations_requires_gestionar_finanzas_permission(): void
    {
        $response = $this->actingAs($this->teacher)
            ->getJson('/api/v1/payment-obligations');

        $response->assertForbidden();
    }

    public function test_show_obligation_requires_gestionar_finanzas_permission(): void
    {
        $response = $this->actingAs($this->teacher)
            ->getJson("/api/v1/payment-obligations/{$this->obligation->id}");

        $response->assertForbidden();
    }

    public function test_adjust_obligation_requires_gestionar_finanzas_permission(): void
    {
        $response = $this->actingAs($this->teacher)
            ->postJson(
                "/api/v1/payment-obligations/{$this->obligation->id}/adjustments",
                [
                    'adjustment_type' => 'discount',
                    'amount' => 50,
                    'reason' => 'Test',
                ]
            );

        $response->assertForbidden();
    }

    public function test_bulk_adjust_requires_gestionar_finanzas_permission(): void
    {
        $response = $this->actingAs($this->teacher)
            ->postJson('/api/v1/payment-obligations/bulk-adjustments', [
                'obligation_ids' => [$this->obligation->id],
                'adjustment_type' => 'discount',
                'amount' => 50,
                'reason' => 'Test',
            ]);

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_access_endpoints(): void
    {
        $response = $this->getJson('/api/v1/payment-obligations');
        $response->assertUnauthorized();

        $response = $this->postJson('/api/v1/payment-obligations', []);
        $response->assertUnauthorized();

        $response = $this->getJson("/api/v1/payment-obligations/{$this->obligation->id}");
        $response->assertUnauthorized();

        $response = $this->postJson(
            "/api/v1/payment-obligations/{$this->obligation->id}/adjustments",
            []
        );
        $response->assertUnauthorized();

        $response = $this->postJson('/api/v1/payment-obligations/bulk-adjustments', []);
        $response->assertUnauthorized();
    }

    public function test_authorized_admin_can_access_all_endpoints(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/payment-obligations');
        $response->assertOk();

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/payment-obligations/{$this->obligation->id}");
        $response->assertOk();
    }
}
