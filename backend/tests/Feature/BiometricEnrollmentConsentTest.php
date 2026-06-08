<?php

use App\Models\Alumno;
use App\Models\User;
use App\Modules\Usuarios\Domain\Models\ArchivoBiometrico;
use App\Modules\Usuarios\Domain\Models\ConsentimientoBiometrico;
use App\Modules\Usuarios\Domain\Models\PerfilFacial;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    config(['biometrics.storage_disk' => 'local']);
    config(['biometrics.storage_prefix' => 'private/biometrics-test']);
    config(['facial-service.url' => 'http://facial-api.test']);
    config(['facial-service.token' => 'test-token']);
    config(['biometrics.embedding_key' => 'base64:'.base64_encode(random_bytes(32))]);
    Http::fake([
        'facial-api.test/v1/enrollments' => Http::response([
            'embedding' => base64_encode(random_bytes(32)),
            'quality' => 0.95,
            'liveness' => 0.9,
            'model_version' => 'test-model-v1',
        ]),
    ]);
    Storage::fake('local');
});

function biometricManager(): User
{
    $manager = User::factory()->create();
    $manager->givePermissionTo('gestionar_dispositivos');

    return $manager;
}

function studentWithAccount(): Alumno
{
    return Alumno::factory()->create(['user_id' => User::factory()->create()->id]);
}

function enrollmentImages(): array
{
    return [
        UploadedFile::fake()->image('front.jpg', 120, 120),
        UploadedFile::fake()->image('left.jpg', 120, 120),
        UploadedFile::fake()->image('right.jpg', 120, 120),
    ];
}

it('grants and lists biometric consent only for device managers', function () {
    $manager = biometricManager();
    $student = studentWithAccount();

    $guestResponse = $this->postJson('/api/v1/biometric-consents', [
        'student_id' => $student->id,
        'legal_basis' => 'Autorización firmada por apoderado.',
    ]);
    $guestResponse->assertUnauthorized();

    $forbidden = User::factory()->create();
    $this->actingAs($forbidden)->postJson('/api/v1/biometric-consents', [
        'student_id' => $student->id,
        'legal_basis' => 'Autorización firmada por apoderado.',
    ])->assertForbidden();

    $response = $this->actingAs($manager)->postJson('/api/v1/biometric-consents', [
        'student_id' => $student->id,
        'legal_basis' => 'Autorización firmada por apoderado.',
        'expires_at' => now()->addYear()->toISOString(),
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.student_id', $student->id)
        ->assertJsonPath('data.status', 'otorgado');

    $this->actingAs($manager)->getJson('/api/v1/biometric-consents')
        ->assertOk()
        ->assertJsonPath('data.0.student_id', $student->id);
});

it('rejects duplicate active consent and enrollment without matching consent', function () {
    $manager = biometricManager();
    $student = studentWithAccount();

    $this->actingAs($manager)->postJson('/api/v1/biometric-consents', [
        'student_id' => $student->id,
        'legal_basis' => 'Autorización firmada por apoderado.',
    ])->assertCreated();

    $this->actingAs($manager)->postJson('/api/v1/biometric-consents', [
        'student_id' => $student->id,
        'legal_basis' => 'Nuevo intento duplicado.',
    ])->assertConflict();

    $otherStudent = studentWithAccount();
    $consent = ConsentimientoBiometrico::firstOrFail();

    $this->actingAs($manager)->post('/api/v1/biometric-enrollments', [
        'student_id' => $otherStudent->id,
        'consent_id' => $consent->id,
        'images' => enrollmentImages(),
    ])->assertConflict();
});

it('enrolls a biometric profile with private files and encrypted embedding', function () {
    $manager = biometricManager();
    $student = studentWithAccount();

    $consentId = $this->actingAs($manager)->postJson('/api/v1/biometric-consents', [
        'student_id' => $student->id,
        'legal_basis' => 'Autorización firmada por apoderado.',
    ])->json('data.id');

    $response = $this->actingAs($manager)->post('/api/v1/biometric-enrollments', [
        'student_id' => $student->id,
        'consent_id' => $consentId,
        'images' => enrollmentImages(),
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.student_id', $student->id)
        ->assertJsonPath('data.active', true)
        ->assertJsonMissingPath('data.embedding_cifrado');

    $profile = PerfilFacial::firstOrFail();
    $encryptedEmbedding = is_resource($profile->embedding_cifrado)
        ? stream_get_contents($profile->embedding_cifrado)
        : (string) $profile->embedding_cifrado;

    expect($encryptedEmbedding)->not->toBeEmpty()
        ->and(str_contains($encryptedEmbedding, 'front.jpg'))->toBeFalse()
        ->and(ArchivoBiometrico::where('perfil_facial_id', $profile->id)->count())->toBe(3);

    ArchivoBiometrico::all()->each(function (ArchivoBiometrico $file): void {
        expect($file->r2_object_key)->toStartWith('private/biometrics-test/')
            ->and($file->r2_object_key)->not->toStartWith('http')
            ->and(Storage::disk('local')->exists($file->r2_object_key))->toBeTrue();
    });
});

it('revokes consent, deactivates active profile, and schedules private file deletion', function () {
    $manager = biometricManager();
    $student = studentWithAccount();

    $consentId = $this->actingAs($manager)->postJson('/api/v1/biometric-consents', [
        'student_id' => $student->id,
        'legal_basis' => 'Autorización firmada por apoderado.',
    ])->json('data.id');

    $this->actingAs($manager)->post('/api/v1/biometric-enrollments', [
        'student_id' => $student->id,
        'consent_id' => $consentId,
        'images' => enrollmentImages(),
    ])->assertCreated();

    $this->actingAs($manager)->postJson("/api/v1/biometric-consents/{$consentId}/revocation", [
        'reason' => 'Solicitud del apoderado.',
    ])->assertOk()
        ->assertJsonPath('data.status', 'revocado');

    expect(PerfilFacial::firstOrFail()->fresh()->activo)->toBeFalse()
        ->and(ArchivoBiometrico::whereNull('expira_en')->count())->toBe(0);
});

it('validates enrollment image counts and exposes stable validation errors', function () {
    $manager = biometricManager();
    $student = studentWithAccount();

    $consentId = $this->actingAs($manager)->postJson('/api/v1/biometric-consents', [
        'student_id' => $student->id,
        'legal_basis' => 'Autorización firmada por apoderado.',
    ])->json('data.id');

    $this->actingAs($manager)->post('/api/v1/biometric-enrollments', [
        'student_id' => $student->id,
        'consent_id' => $consentId,
        'images' => [UploadedFile::fake()->image('one.jpg'), UploadedFile::fake()->image('two.jpg')],
    ])->assertUnprocessable()
        ->assertJsonPath('error.code', 'validation_failed');
});
