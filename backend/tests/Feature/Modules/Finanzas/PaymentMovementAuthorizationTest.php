<?php

namespace Tests\Feature\Modules\Finanzas;

use App\Modules\Finanzas\Infrastructure\Models\MovimientoPago;
use App\Modules\Finanzas\Infrastructure\Models\ObligacionPago;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentMovementAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $unauthorized;

    protected User $yanina;

    protected ObligacionPago $obligation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->yanina = User::factory()->create();
        $this->yanina->assignRole('administrativo');
        $this->yanina->givePermissionTo('gestionar_finanzas');

        $this->unauthorized = User::factory()->create();

        $this->obligation = ObligacionPago::factory()->create([
            'estado' => 'pendiente',
            'monto_base_snapshot' => 500,
            'monto_ordinario_snapshot' => 500,
            'monto_pronto_pago_snapshot' => 500,
        ]);
    }

    public function test_create_movement_requires_gestionar_finanzas(): void
    {
        $response = $this->actingAs($this->unauthorized)
            ->postJson('/api/v1/payment-movements', [
                'obligation_id' => $this->obligation->id,
                'movement_type' => 'payment',
                'amount' => '100.00',
                'occurred_at' => now()->format('Y-m-d\TH:i:s'),
                'method' => 'cash',
            ]);

        $response->assertForbidden();
    }

    public function test_download_receipt_requires_gestionar_finanzas(): void
    {
        $movement = MovimientoPago::create([
            'obligacion_pago_id' => $this->obligation->id,
            'tipo' => 'pago',
            'monto' => 100.00,
            'medio_pago' => 'efectivo',
            'numero_recibo' => 'REC-2026-00001',
            'registrado_por' => $this->yanina->id,
        ]);

        $response = $this->actingAs($this->unauthorized)
            ->getJson("/api/v1/payment-movements/{$movement->id}/receipt");

        $response->assertForbidden();
    }

    public function test_authorized_user_can_create_payment(): void
    {
        $response = $this->actingAs($this->yanina)
            ->postJson('/api/v1/payment-movements', [
                'obligation_id' => $this->obligation->id,
                'movement_type' => 'payment',
                'amount' => '500.00',
                'occurred_at' => now()->format('Y-m-d\TH:i:s'),
                'method' => 'cash',
            ]);

        $response->assertStatus(201);
    }

    public function test_unauthenticated_user_cannot_access_endpoints(): void
    {
        $response = $this->postJson('/api/v1/payment-movements', [
            'obligation_id' => $this->obligation->id,
            'movement_type' => 'payment',
            'amount' => '100.00',
            'occurred_at' => now()->format('Y-m-d\TH:i:s'),
            'method' => 'cash',
        ]);

        $response->assertUnauthorized();
    }
}
