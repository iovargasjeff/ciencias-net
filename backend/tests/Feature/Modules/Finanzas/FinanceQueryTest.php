<?php

namespace Tests\Feature\Modules\Finanzas;

use App\Modules\Academico\Infrastructure\Models\PeriodoAcademico;
use App\Modules\Finanzas\Application\Jobs\SendPaymentRemindersJob;
use App\Modules\Finanzas\Infrastructure\Models\ConceptoPago;
use App\Modules\Finanzas\Infrastructure\Models\MovimientoPago;
use App\Modules\Finanzas\Infrastructure\Models\ObligacionPago;
use App\Modules\Usuarios\Infrastructure\Models\Alumno;
use App\Modules\Usuarios\Infrastructure\Models\Padre;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class FinanceQueryTest extends TestCase
{
    use RefreshDatabase;

    protected User $yanina;

    protected PeriodoAcademico $period;

    protected ConceptoPago $concept;

    protected Alumno $student;

    protected ObligacionPago $obligation;

    protected User $studentUser;

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
            'fecha_limite_pronto_pago' => Carbon::parse('2026-06-15'),
            'creado_por' => $this->yanina->id,
        ]);

        $this->studentUser = User::factory()->create();
        $this->studentUser->assignRole('alumno');
        $this->student = Alumno::factory()->create([
            'user_id' => $this->studentUser->id,
        ]);

        $this->obligation = ObligacionPago::factory()->create([
            'alumno_id' => $this->student->id,
            'concepto_id' => $this->concept->id,
            'monto_base_snapshot' => 500,
            'monto_ordinario_snapshot' => 500,
            'monto_pronto_pago_snapshot' => 450,
            'descuento_pronto_pago_aplicado' => 50,
            'fecha_limite_pronto_pago_snapshot' => Carbon::parse('2026-06-15'),
            'estado' => 'pendiente',
        ]);
    }

    public function test_student_can_view_own_account_statements(): void
    {
        $response = $this->actingAs($this->studentUser)
            ->getJson('/api/v1/account-statements');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.student.id', $this->student->id);
    }

    public function test_parent_can_view_linked_student_account_statements(): void
    {
        $parentUser = User::factory()->create();
        $parentUser->assignRole('padre');
        $parent = Padre::create([
            'user_id' => $parentUser->id,
            'dni' => '12345678',
            'nombres' => 'Juan',
            'apellidos' => 'Perez',
            'celular' => '999888777',
            'correo_notificaciones' => 'juan@example.test',
        ]);

        DB::table('alumno_padre')->insert([
            'id' => (string) Str::uuid(),
            'alumno_id' => $this->student->id,
            'padre_id' => $parent->id,
            'relacion' => 'padre',
            'es_contacto_principal' => true,
            'recibe_notificaciones' => true,
        ]);

        $response = $this->actingAs($parentUser)
            ->getJson("/api/v1/account-statements?student_id={$this->student->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_parent_cannot_view_unlinked_student_account_statements(): void
    {
        $parentUser = User::factory()->create();
        $parentUser->assignRole('padre');
        Padre::create([
            'user_id' => $parentUser->id,
            'dni' => '87654321',
            'nombres' => 'Jose',
            'apellidos' => 'Gomez',
            'celular' => '999888776',
            'correo_notificaciones' => 'jose@example.test',
        ]);

        $response = $this->actingAs($parentUser)
            ->getJson("/api/v1/account-statements?student_id={$this->student->id}");

        $response->assertStatus(403);
    }

    public function test_admin_can_view_any_account_statement(): void
    {
        $response = $this->actingAs($this->yanina)
            ->getJson("/api/v1/account-statements?student_id={$this->student->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_shows_early_payment_amount_before_deadline_and_ordinary_after(): void
    {
        // 1. Before deadline: e.g. June 10, 2026
        Carbon::setTestNow(Carbon::parse('2026-06-10 12:00:00'));

        $response = $this->actingAs($this->studentUser)
            ->getJson('/api/v1/account-statements');

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.amounts.applicable_amount', 450);

        // 2. After deadline: e.g. June 16, 2026
        Carbon::setTestNow(Carbon::parse('2026-06-16 12:00:00'));

        $response2 = $this->actingAs($this->studentUser)
            ->getJson('/api/v1/account-statements');

        $response2->assertStatus(200);
        $response2->assertJsonPath('data.0.amounts.applicable_amount', 500);

        Carbon::setTestNow(); // Reset time faking
    }

    public function test_admin_can_list_debtors(): void
    {
        // Set date to after due date so the student is a debtor
        // Due date is by default from factory, let's update it to yesterday
        $this->obligation->update([
            'fecha_vencimiento' => Carbon::yesterday(),
            'estado' => 'pendiente',
        ]);

        $response = $this->actingAs($this->yanina)
            ->getJson('/api/v1/finance/debtors');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.student.id', $this->student->id);
        $response->assertJsonPath('data.0.overdue_count', 1);
        $response->assertJsonPath('data.0.total_overdue_amount', 500);
    }

    public function test_admin_can_get_cash_report(): void
    {
        $admin = $this->yanina;

        // Register a payment movement
        MovimientoPago::create([
            'obligacion_pago_id' => $this->obligation->id,
            'tipo' => 'pago',
            'monto' => 450.00,
            'medio_pago' => 'transferencia',
            'referencia' => 'TXN-00001',
            'numero_recibo' => 'REC-2026-00001',
            'registrado_por' => $admin->id,
        ]);

        $this->obligation->update([
            'estado' => 'pagado',
            'monto_cobrado' => 450.00,
            'fecha_pago' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/finance/cash-reports?date_from='.now()->toDateString().'&date_to='.now()->toDateString());

        $response->assertStatus(200);
        $response->assertJsonPath('data.total_collected', 450);
        $response->assertJsonPath('data.net_amount', 450);
        $response->assertJsonPath('data.by_method.transferencia', 450);
    }

    public function test_admin_can_send_reminders_and_idempotency(): void
    {
        Queue::fake();

        $key = (string) Str::uuid();

        $response = $this->actingAs($this->yanina)
            ->withHeader('Idempotency-Key', $key)
            ->postJson('/api/v1/payment-reminders', [
                'obligation_ids' => [$this->obligation->id],
                'channel' => 'both',
            ]);

        $response->assertStatus(202);
        $response->assertJsonPath('data.status', 'pending');

        Queue::assertPushed(SendPaymentRemindersJob::class, function ($job) {
            return $job->obligationIds === [$this->obligation->id] && $job->channel === 'both';
        });

        // Replay request with same Idempotency-Key
        $replayResponse = $this->actingAs($this->yanina)
            ->withHeader('Idempotency-Key', $key)
            ->postJson('/api/v1/payment-reminders', [
                'obligation_ids' => [$this->obligation->id],
                'channel' => 'both',
            ]);

        $replayResponse->assertStatus(202);
        $replayResponse->assertHeader('Idempotency-Replayed', 'true');
    }

    public function test_job_creates_notifications_and_emails(): void
    {
        $parentUser = User::factory()->create();
        $parentUser->assignRole('padre');
        $parent = Padre::create([
            'user_id' => $parentUser->id,
            'dni' => '12345678',
            'nombres' => 'Juan',
            'apellidos' => 'Perez',
            'celular' => '999888777',
            'correo_notificaciones' => 'juan@example.test',
        ]);

        DB::table('alumno_padre')->insert([
            'id' => (string) Str::uuid(),
            'alumno_id' => $this->student->id,
            'padre_id' => $parent->id,
            'relacion' => 'padre',
            'es_contacto_principal' => true,
            'recibe_notificaciones' => true,
        ]);

        $job = new SendPaymentRemindersJob([$this->obligation->id], 'both');
        $job->handle();

        // Should have created 2 records in notificaciones table (1 for panel, 1 for email log)
        $this->assertDatabaseHas('notificaciones', [
            'user_id' => $parentUser->id,
            'canal' => 'panel',
            'tipo' => 'pago',
        ]);

        $this->assertDatabaseHas('notificaciones', [
            'user_id' => $parentUser->id,
            'canal' => 'correo',
            'tipo' => 'pago',
        ]);
    }
}
