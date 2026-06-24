<?php

namespace Tests\Feature;

use App\Modules\Usuarios\Infrastructure\Models\User;
use App\Support\AuditLogger;
use App\Support\SensitiveDataRedactor;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SecurityObservabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_responses_include_security_and_correlation_headers(): void
    {
        $requestId = 'req-test-123456';

        $this->withHeader('X-Request-Id', $requestId)
            ->getJson('/api/v1/health')
            ->assertOk()
            ->assertHeader('X-Request-Id', $requestId)
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_authenticated_api_rate_limit_is_applied(): void
    {
        Role::create(['name' => 'superadmin']);
        $user = User::factory()->create();
        $user->assignRole('superadmin');

        Route::middleware(['api', 'auth:sanctum', 'throttle:test-tight-limit'])
            ->get('/api/v1/test-tight-limit', fn () => response()->json(['data' => ['ok' => true]]));

        RateLimiter::for('test-tight-limit', fn ($request) => [
            Limit::perMinute(1)->by($request->user()?->id),
        ]);

        $this->actingAs($user)->getJson('/api/v1/test-tight-limit')->assertOk();
        $this->actingAs($user)->getJson('/api/v1/test-tight-limit')->assertTooManyRequests();
    }

    public function test_audit_logger_redacts_sensitive_values(): void
    {
        $user = User::factory()->create();

        app(AuditLogger::class)->record(
            null,
            'security.redaction_test',
            $user,
            $user,
            newValues: [
                'password' => 'secret-password',
                'token' => 'secret-token',
                'notas_privadas' => 'contenido privado',
                'safe' => 'visible',
            ],
        );

        $payload = json_decode((string) DB::table('audit_logs')->where('action', 'security.redaction_test')->value('new_values'), true);

        $this->assertSame('[REDACTED]', $payload['password']);
        $this->assertSame('[REDACTED]', $payload['token']);
        $this->assertSame('[REDACTED]', $payload['notas_privadas']);
        $this->assertSame('visible', $payload['safe']);
    }

    public function test_redactor_handles_nested_sensitive_context(): void
    {
        $redacted = SensitiveDataRedactor::redact([
            'metadata' => [
                'api_key' => 'abc',
                'normal' => 'ok',
            ],
        ]);

        $this->assertSame('[REDACTED]', $redacted['metadata']['api_key']);
        $this->assertSame('ok', $redacted['metadata']['normal']);
    }
}
