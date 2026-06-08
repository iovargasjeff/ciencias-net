<?php

use App\Modules\Usuarios\Infrastructure\Models\User;
use App\Modules\Asistencia\Domain\Models\EventoReconocimiento;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    config(['facial-service.url' => 'http://facial-api.test']);
    config(['facial-service.token' => 'internal-token']);
    config(['biometrics.storage_prefix' => 'private/biometrics-test']);
    config(['biometrics.embedding_key' => 'base64:'.base64_encode(random_bytes(32))]);
    Storage::fake('local');
    Http::fake([
        'facial-api.test/v1/identifications' => Http::response([
            'matched' => false,
            'candidate_id' => null,
            'confidence' => 0.0,
            'quality' => 0.8,
            'liveness' => 0.8,
            'model_version' => 'test-model-v1',
        ]),
    ]);
});

function stationManager(): User
{
    $manager = User::factory()->create();
    $manager->givePermissionTo('gestionar_dispositivos');

    return $manager;
}

function createStationThroughApi(User $manager): string
{
    return test()->actingAs($manager)->postJson('/api/v1/stations', [
        'name' => 'Puerta Principal',
        'location' => 'Ingreso principal',
        'mode' => 'mixed',
    ])->assertCreated()->json('data.id');
}

function createCameraThroughApi(User $manager, string $stationId): string
{
    return test()->actingAs($manager)->postJson("/api/v1/stations/{$stationId}/cameras", [
        'label' => 'Cámara frontal',
        'device_identifier' => 'camera-device-1',
    ])->assertCreated()->json('data.id');
}

function stationCookieValue($response): string
{
    foreach ($response->headers->all('set-cookie') as $cookie) {
        if (str_starts_with($cookie, 'cienciasnet_station_session=')) {
            return urldecode(strtok(substr($cookie, strlen('cienciasnet_station_session=')), ';'));
        }
    }

    throw new RuntimeException('Station cookie was not set.');
}

function activationCodeFor(User $manager, string $stationId): string
{
    return test()->actingAs($manager)->postJson("/api/v1/stations/{$stationId}/activation-codes")
        ->assertCreated()
        ->json('data.activation_code');
}

it('manages stations and cameras only with device permission', function () {
    $manager = stationManager();
    $forbidden = User::factory()->create();

    $this->actingAs($forbidden)->postJson('/api/v1/stations', [
        'name' => 'Puerta Principal',
        'location' => 'Ingreso principal',
        'mode' => 'mixed',
    ])->assertForbidden();

    $stationId = createStationThroughApi($manager);

    $this->actingAs($manager)->patchJson("/api/v1/stations/{$stationId}", [
        'name' => 'Puerta secundaria',
        'active' => true,
    ])->assertOk()->assertJsonPath('data.name', 'Puerta secundaria');

    $cameraId = createCameraThroughApi($manager, $stationId);

    $this->actingAs($manager)->getJson("/api/v1/stations/{$stationId}/cameras")
        ->assertOk()
        ->assertJsonPath('data.0.id', $cameraId);
});

it('activates a station once and rejects reused activation codes', function () {
    $manager = stationManager();
    $stationId = createStationThroughApi($manager);
    $code = activationCodeFor($manager, $stationId);

    $response = $this->postJson('/api/v1/station-activations', [
        'activation_code' => $code,
        'device_name' => 'Navegador recepción',
    ]);

    $response->assertOk()
        ->assertCookie('cienciasnet_station_session')
        ->assertJsonPath('data.id', $stationId);

    $this->postJson('/api/v1/station-activations', [
        'activation_code' => $code,
        'device_name' => 'Segundo navegador',
    ])->assertConflict();
});

it('allows technical station session and capture but blocks human routes', function () {
    $manager = stationManager();
    $stationId = createStationThroughApi($manager);
    $cameraId = createCameraThroughApi($manager, $stationId);
    $code = activationCodeFor($manager, $stationId);

    $activation = $this->postJson('/api/v1/station-activations', [
        'activation_code' => $code,
        'device_name' => 'Navegador recepción',
    ])->assertOk();

    $cookie = stationCookieValue($activation);

    $this->withHeader('Authorization', 'Bearer '.$cookie)
        ->getJson('/api/v1/station/session')
        ->assertOk()
        ->assertJsonPath('data.id', $stationId);

    $this->withHeader('Authorization', 'Bearer '.$cookie)
        ->withHeader('Idempotency-Key', 'capture-station-001')
        ->post('/api/v1/station/captures', [
            'camera_id' => $cameraId,
            'captured_at' => now()->toISOString(),
            'image' => UploadedFile::fake()->image('capture.jpg'),
        ])->assertCreated()
        ->assertJsonPath('data.camera_id', $cameraId)
        ->assertJsonPath('data.status', 'pending_review');

    expect(EventoReconocimiento::where('idempotency_key', 'capture-station-001')->exists())->toBeTrue();

    $this->withHeader('Authorization', 'Bearer '.$cookie)
        ->getJson('/api/v1/accounts')
        ->assertForbidden();
});

it('revokes a station and invalidates its technical session', function () {
    $manager = stationManager();
    $stationId = createStationThroughApi($manager);
    $code = activationCodeFor($manager, $stationId);

    $activation = $this->postJson('/api/v1/station-activations', [
        'activation_code' => $code,
        'device_name' => 'Navegador recepción',
    ])->assertOk();
    $cookie = stationCookieValue($activation);

    $this->actingAs($manager)->postJson("/api/v1/stations/{$stationId}/revocation", [
        'reason' => 'Equipo retirado.',
    ])->assertOk()
        ->assertJsonPath('data.active', false);

    $this->withHeader('Authorization', 'Bearer '.$cookie)
        ->getJson('/api/v1/station/session')
        ->assertUnauthorized();
});
