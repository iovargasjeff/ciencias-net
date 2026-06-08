<?php

namespace App\Modules\Usuarios\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Usuarios\Infrastructure\Models\Alumno;
use App\Modules\Usuarios\Domain\Models\ArchivoBiometrico;
use App\Modules\Usuarios\Domain\Models\ConsentimientoBiometrico;
use App\Modules\Usuarios\Domain\Models\PerfilFacial;
use App\Modules\Usuarios\Infrastructure\Facial\FacialServiceClient;
use App\Modules\Usuarios\Infrastructure\Facial\FacialServiceUnavailable;
use App\Modules\Usuarios\Infrastructure\Security\BiometricEmbeddingEncryptor;
use App\Modules\Usuarios\Presentation\Requests\Biometrics\EnrollBiometricProfileRequest;
use App\Modules\Usuarios\Presentation\Requests\Biometrics\GrantBiometricConsentRequest;
use App\Modules\Usuarios\Presentation\Requests\Biometrics\RevokeBiometricConsentRequest;
use App\Modules\Usuarios\Presentation\Resources\BiometricConsentResource;
use App\Modules\Usuarios\Presentation\Resources\BiometricProfileResource;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BiometricController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()?->can('gestionar_dispositivos'), 403);

        $query = ConsentimientoBiometrico::query()
            ->with('user.alumno')
            ->latest();

        $query->when($request->filled('student_id'), function ($query) use ($request): void {
            $query->whereHas('user.alumno', fn ($inner) => $inner->whereKey($request->string('student_id')));
        });

        return BiometricConsentResource::collection($query->paginate(min($request->integer('per_page', 20), 100)));
    }

    public function store(GrantBiometricConsentRequest $request, AuditLogger $audit): JsonResponse
    {
        $student = Alumno::with('user')->findOrFail($request->string('student_id'));

        if ($student->user === null) {
            throw new ConflictHttpException('El alumno no tiene cuenta humana vinculada.');
        }

        $consent = DB::transaction(function () use ($request, $student): ConsentimientoBiometrico {
            if (ConsentimientoBiometrico::where('user_id', $student->user_id)->where('estado', 'otorgado')->exists()) {
                throw new ConflictHttpException('La persona ya tiene consentimiento biométrico otorgado.');
            }

            return ConsentimientoBiometrico::create([
                'user_id' => $student->user_id,
                'estado' => 'otorgado',
                'otorgado_por' => $request->user()->id,
                'documento_version' => config('biometrics.consent_document_version'),
                'fundamento_legal' => $request->string('legal_basis')->toString(),
                'otorgado_en' => now(),
                'expira_en' => $request->date('expires_at'),
            ]);
        });

        $audit->record($request, 'biometric_consent.granted', $request->user(), $consent, newValues: [
            'student_id' => $student->id,
            'status' => $consent->estado,
        ]);

        return response()->json(['data' => new BiometricConsentResource($consent->load('user.alumno'))], 201);
    }

    public function revoke(RevokeBiometricConsentRequest $request, string $consentId, AuditLogger $audit): JsonResponse
    {
        $consent = ConsentimientoBiometrico::with('user.alumno')->findOrFail($consentId);

        if ($consent->estado !== 'otorgado') {
            throw new ConflictHttpException('Solo se puede revocar un consentimiento otorgado.');
        }

        DB::transaction(function () use ($request, $consent): void {
            $consent->update([
                'estado' => 'revocado',
                'revocado_en' => now(),
                'motivo_revocacion' => $request->string('reason')->toString(),
            ]);

            PerfilFacial::where('user_id', $consent->user_id)
                ->where('activo', true)
                ->update(['activo' => false, 'ultima_actualizacion_en' => now(), 'updated_at' => now()]);

            ArchivoBiometrico::where('user_id', $consent->user_id)
                ->whereNull('eliminado_en')
                ->whereNull('expira_en')
                ->update(['expira_en' => now()->addDays(30)]);
        });

        $audit->record($request, 'biometric_consent.revoked', $request->user(), $consent, newValues: [
            'reason' => 'redacted',
        ]);

        return response()->json(['data' => new BiometricConsentResource($consent->refresh()->load('user.alumno'))]);
    }

    public function enroll(
        EnrollBiometricProfileRequest $request,
        BiometricEmbeddingEncryptor $encryptor,
        FacialServiceClient $facialService,
        AuditLogger $audit,
    ): JsonResponse {
        $student = Alumno::with('user')->findOrFail($request->string('student_id'));
        $consent = ConsentimientoBiometrico::findOrFail($request->string('consent_id'));

        if ($student->user === null || $consent->user_id !== $student->user_id || $consent->estado !== 'otorgado') {
            throw new ConflictHttpException('El enrolamiento requiere consentimiento otorgado para el alumno.');
        }

        if ($consent->expira_en !== null && $consent->expira_en->isPast()) {
            throw new ConflictHttpException('El consentimiento biométrico expiró.');
        }

        $disk = Storage::disk(config('biometrics.storage_disk'));
        $prefix = trim((string) config('biometrics.storage_prefix'), '/');
        $hashes = [];

        $profile = DB::transaction(function () use ($request, $student, $encryptor, $facialService, $audit, $disk, $prefix, &$hashes): PerfilFacial {
            PerfilFacial::where('user_id', $student->user_id)
                ->where('activo', true)
                ->update(['activo' => false, 'ultima_actualizacion_en' => now(), 'updated_at' => now()]);

            foreach ($request->file('images', []) as $image) {
                $hash = hash_file('sha256', $image->getRealPath());
                $hashes[] = $hash;
            }

            try {
                $embeddingResult = $facialService->createEmbedding($request->file('images', []));
            } catch (FacialServiceUnavailable) {
                throw new HttpException(503, 'El servicio facial no está disponible.');
            }

            $profile = PerfilFacial::create([
                'user_id' => $student->user_id,
                'embedding_cifrado' => $encryptor->encrypt($embeddingResult['embedding']),
                'modelo_version' => $embeddingResult['model_version'],
                'calidad' => $embeddingResult['quality'],
                'activo' => true,
                'enrolado_por' => $request->user()->id,
                'enrolado_en' => now(),
                'ultima_actualizacion_en' => now(),
            ]);

            foreach ($request->file('images', []) as $index => $image) {
                $extension = $image->guessExtension() ?: $image->extension() ?: 'jpg';
                $objectKey = sprintf('%s/%s/%s.%s', $prefix, $profile->id, Str::uuid(), $extension);
                $disk->put($objectKey, file_get_contents($image->getRealPath()), ['visibility' => 'private']);

                ArchivoBiometrico::create([
                    'user_id' => $student->user_id,
                    'perfil_facial_id' => $profile->id,
                    'tipo' => 'enrolamiento',
                    'r2_object_key' => $objectKey,
                    'sha256' => $hashes[$index],
                    'mime_type' => $image->getMimeType() ?: 'application/octet-stream',
                ]);
            }

            $audit->record($request, 'biometric_profile.enrolled', $request->user(), $profile, newValues: [
                'student_id' => $student->id,
                'images' => count($hashes),
            ]);

            return $profile;
        });

        return response()->json(['data' => new BiometricProfileResource($profile->load('user.alumno'))], 201);
    }
}
