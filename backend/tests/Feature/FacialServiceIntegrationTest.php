<?php

use App\Models\Alumno;
use App\Models\PerfilFacial;
use App\Models\User;
use App\Support\Facial\FacialServiceClient;
use App\Support\Facial\FacialServiceUnavailable;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    config(['facial-service.url' => 'http://facial-api.test']);
    config(['facial-service.token' => 'internal-token']);
    config(['facial-service.timeout' => 5]);
    config(['biometrics.storage_disk' => 'local']);
    config(['biometrics.storage_prefix' => 'private/biometrics-test']);
    Storage::fake('local');
});

it('calls the private facial enrollment contract with service token and multipart images', function () {
    Http::fake([
        'facial-api.test/v1/enrollments' => Http::response([
            'embedding' => base64_encode(random_bytes(32)),
            'quality' => 0.91,
            'liveness' => 0.88,
            'model_version' => 'facial-test-v1',
        ]),
    ]);

    $result = app(FacialServiceClient::class)->createEmbedding([
        UploadedFile::fake()->image('front.jpg'),
        UploadedFile::fake()->image('left.jpg'),
        UploadedFile::fake()->image('right.jpg'),
    ]);

    expect($result['model_version'])->toBe('facial-test-v1')
        ->and($result['quality'])->toBe(0.91);

    Http::assertSent(fn ($request) => $request->hasHeader('X-Facial-Service-Token', 'internal-token')
        && $request->url() === 'http://facial-api.test/v1/enrollments');
});

it('calls the private facial identification contract with idempotency and opaque candidates', function () {
    Http::fake([
        'facial-api.test/v1/identifications' => Http::response([
            'matched' => true,
            'candidate_id' => 'candidate-1',
            'confidence' => 0.93,
            'quality' => 0.9,
            'liveness' => 0.87,
            'model_version' => 'facial-test-v1',
        ]),
    ]);

    $result = app(FacialServiceClient::class)->identify(
        UploadedFile::fake()->image('capture.jpg'),
        [['id' => 'candidate-1', 'embedding' => base64_encode(random_bytes(32))]],
        'capture-key-001'
    );

    expect($result['matched'])->toBeTrue()
        ->and($result['candidate_id'])->toBe('candidate-1');

    Http::assertSent(fn ($request) => $request->hasHeader('X-Facial-Service-Token', 'internal-token')
        && $request->hasHeader('Idempotency-Key', 'capture-key-001'));
});

it('does not create an enrollment profile when the facial service times out', function () {
    Http::fake(fn () => throw new ConnectionException('timeout'));

    expect(fn () => app(FacialServiceClient::class)->createEmbedding([
        UploadedFile::fake()->image('front.jpg'),
        UploadedFile::fake()->image('left.jpg'),
        UploadedFile::fake()->image('right.jpg'),
    ]))->toThrow(FacialServiceUnavailable::class);
});

it('enrollment endpoint returns unavailable and persists no profile when Python times out', function () {
    Http::fake(fn () => throw new ConnectionException('timeout'));

    $manager = User::factory()->create();
    $manager->givePermissionTo('gestionar_dispositivos');
    $student = Alumno::factory()->create(['user_id' => User::factory()->create()->id]);

    $consentId = $this->actingAs($manager)->postJson('/api/v1/biometric-consents', [
        'student_id' => $student->id,
        'legal_basis' => 'Autorización firmada por apoderado.',
    ])->json('data.id');

    $this->actingAs($manager)->post('/api/v1/biometric-enrollments', [
        'student_id' => $student->id,
        'consent_id' => $consentId,
        'images' => [
            UploadedFile::fake()->image('front.jpg'),
            UploadedFile::fake()->image('left.jpg'),
            UploadedFile::fake()->image('right.jpg'),
        ],
    ])->assertServiceUnavailable();

    expect(PerfilFacial::count())->toBe(0);
});
