<?php

use App\Models\Alumno;
use App\Modules\Asistencia\Domain\Models\AsistenciaAlumno;
use App\Modules\Asistencia\Domain\Models\CamaraEstacion;
use App\Modules\Usuarios\Domain\Models\ConsentimientoBiometrico;
use App\Modules\Asistencia\Domain\Models\EstacionBiometrica;
use App\Modules\Asistencia\Domain\Models\MovimientoAsistencia;
use App\Models\Padre;
use App\Modules\Usuarios\Domain\Models\PerfilFacial;
use App\Models\User;
use App\Notifications\StudentAttendanceMovementNotification;
use App\Modules\Asistencia\Domain\Services\StudentAttendanceProcessor;
use App\Modules\Usuarios\Infrastructure\Security\BiometricEmbeddingEncryptor;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    config(['facial-service.url' => 'http://facial-api.test']);
    config(['facial-service.token' => 'internal-token']);
    config(['facial-service.timeout' => 5]);
    config(['biometrics.embedding_key' => 'base64:' . base64_encode(random_bytes(32))]);
    config(['biometrics.storage_prefix' => 'private/biometrics-test']);
    config(['biometrics.storage_disk' => 'local']);
    Storage::fake('local');
    Notification::fake();
});

function attendanceManager(): User
{
    $manager = User::factory()->create();
    $manager->givePermissionTo('gestionar_dispositivos');
    $manager->assignRole('auxiliar');

    return $manager;
}

function attendanceStudent(): Alumno
{
    return Alumno::factory()->create(['user_id' => User::factory()->create()->id]);
}

function createAttendanceStation(User $manager, string $mode = 'mixed'): array
{
    $stationId = test()->actingAs($manager)->postJson('/api/v1/stations', [
        'name' => 'Puerta '.$mode,
        'location' => 'Ingreso principal',
        'mode' => $mode,
    ])->assertCreated()->json('data.id');
    $cameraId = test()->actingAs($manager)->postJson("/api/v1/stations/{$stationId}/cameras", [
        'label' => 'Cámara '.$mode,
        'device_identifier' => 'camera-'.$mode,
    ])->assertCreated()->json('data.id');
    $code = test()->actingAs($manager)->postJson("/api/v1/stations/{$stationId}/activation-codes")
        ->assertCreated()
        ->json('data.activation_code');
    $activation = test()->postJson('/api/v1/station-activations', [
        'activation_code' => $code,
        'device_name' => 'Navegador '.$mode,
    ])->assertOk();

    return [$stationId, $cameraId, stationAttendanceCookieValue($activation)];
}

function stationAttendanceCookieValue($response): string
{
    foreach ($response->headers->all('set-cookie') as $cookie) {
        if (str_starts_with($cookie, 'cienciasnet_station_session=')) {
            return urldecode(strtok(substr($cookie, strlen('cienciasnet_station_session=')), ';'));
        }
    }

    throw new RuntimeException('Station cookie was not set.');
}

function createActiveFacialProfile(Alumno $student, User $manager): PerfilFacial
{
    ConsentimientoBiometrico::create([
        'user_id' => $student->user_id,
        'estado' => 'otorgado',
        'otorgado_por' => $manager->id,
        'documento_version' => 'v1',
        'fundamento_legal' => 'Autorización firmada.',
        'otorgado_en' => now(),
    ]);

    $embedding = base64_encode(random_bytes(32));

    return PerfilFacial::create([
        'user_id' => $student->user_id,
        'embedding_cifrado' => app(BiometricEmbeddingEncryptor::class)->encrypt($embedding),
        'modelo_version' => 'test-model-v1',
        'calidad' => 0.95,
        'activo' => true,
        'enrolado_por' => $manager->id,
        'enrolado_en' => now(),
        'ultima_actualizacion_en' => now(),
    ]);
}

function fakeFacialMatch(Alumno $student, float $confidence = 0.93): void
{
    Http::fake([
        'facial-api.test/v1/identifications' => Http::response([
            'matched' => true,
            'candidate_id' => $student->user_id,
            'confidence' => $confidence,
            'quality' => 0.9,
            'liveness' => 0.9,
            'model_version' => 'test-model-v1',
        ]),
    ]);
}

it('alternates bidirectional facial captures as entry and exit without duplicating idempotent retries', function () {
    $manager = attendanceManager();
    $student = attendanceStudent();
    createActiveFacialProfile($student, $manager);
    [$stationId, $cameraId, $token] = createAttendanceStation($manager, 'mixed');
    fakeFacialMatch($student);

    $first = $this->withHeader('Authorization', 'Bearer '.$token)
        ->withHeader('Idempotency-Key', 'student-capture-001')
        ->post('/api/v1/station/captures', [
            'camera_id' => $cameraId,
            'captured_at' => '2026-06-07T07:30:00-05:00',
            'image' => UploadedFile::fake()->image('capture-1.jpg'),
        ])->assertCreated()
        ->assertJsonPath('data.event_type', 'entry');

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->withHeader('Idempotency-Key', 'student-capture-001')
        ->post('/api/v1/station/captures', [
            'camera_id' => $cameraId,
            'captured_at' => '2026-06-07T07:30:00-05:00',
            'image' => UploadedFile::fake()->image('capture-1.jpg'),
        ])->assertCreated()
        ->assertJsonPath('data.movement_id', $first->json('data.movement_id'));

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->withHeader('Idempotency-Key', 'student-capture-002')
        ->post('/api/v1/station/captures', [
            'camera_id' => $cameraId,
            'captured_at' => '2026-06-07T12:00:00-05:00',
            'image' => UploadedFile::fake()->image('capture-2.jpg'),
        ])->assertCreated()
        ->assertJsonPath('data.event_type', 'exit');

    expect(MovimientoAsistencia::pluck('tipo')->all())->toBe(['ingreso', 'salida'])
        ->and(MovimientoAsistencia::count())->toBe(2)
        ->and(EstacionBiometrica::find($stationId)->exists())->toBeTrue();
});

it('uses fixed entry station mode and marks late arrivals after the punctual limit', function () {
    $manager = attendanceManager();
    $student = attendanceStudent();
    createActiveFacialProfile($student, $manager);
    [, $cameraId, $token] = createAttendanceStation($manager, 'entry');
    fakeFacialMatch($student);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->withHeader('Idempotency-Key', 'student-entry-fixed-001')
        ->post('/api/v1/station/captures', [
            'camera_id' => $cameraId,
            'captured_at' => '2026-06-07T08:10:00-05:00',
            'image' => UploadedFile::fake()->image('capture.jpg'),
        ])->assertCreated()
        ->assertJsonPath('data.event_type', 'entry');

    expect(AsistenciaAlumno::firstOrFail()->estado)->toBe('tardanza')
        ->and(MovimientoAsistencia::firstOrFail()->tipo)->toBe('ingreso')
        ->and(CamaraEstacion::find($cameraId)->modo)->toBe('entrada');
});

it('creates an audited emergency manual exit and notifies linked parent', function () {
    $manager = attendanceManager();
    $student = attendanceStudent();
    $parent = Padre::factory()->create();
    $parent->user->assignRole('padre');
    $student->padres()->attach($parent, ['relacion' => 'Madre', 'recibe_notificaciones' => true]);

    $this->actingAs($manager)
        ->withHeader('Idempotency-Key', 'manual-emergency-001')
        ->postJson('/api/v1/student-attendance/manual-events', [
            'student_id' => $student->id,
            'event_type' => 'exit',
            'occurred_at' => '2026-06-07T10:00:00-05:00',
            'reason' => 'Emergencia médica autorizada por auxiliar.',
        ])->assertCreated()
        ->assertJsonPath('data.type', 'exit')
        ->assertJsonPath('data.reason', 'emergencia');

    expect(MovimientoAsistencia::firstOrFail()->notificacion_enviada)->toBeTrue();
    Notification::assertSentTo($parent->user, StudentAttendanceMovementNotification::class);
});

it('limits parent attendance history to linked students', function () {
    $manager = attendanceManager();
    $linked = attendanceStudent();
    $unrelated = attendanceStudent();
    $parent = Padre::factory()->create();
    $parent->user->assignRole('padre');
    $linked->padres()->attach($parent, ['relacion' => 'Padre']);

    app(StudentAttendanceProcessor::class)->processManualEvent($linked, 'entry', now(), 'Ingreso manual', $manager);
    app(StudentAttendanceProcessor::class)->processManualEvent($unrelated, 'entry', now(), 'Ingreso manual', $manager);

    $this->actingAs($parent->user)->getJson('/api/v1/student-attendance')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.student_id', $linked->id);
});
