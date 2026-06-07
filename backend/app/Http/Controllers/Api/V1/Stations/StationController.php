<?php

namespace App\Http\Controllers\Api\V1\Stations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Stations\ActivateStationRequest;
use App\Http\Requests\Stations\CreateStationCameraRequest;
use App\Http\Requests\Stations\CreateStationRequest;
use App\Http\Requests\Stations\ReasonRequest;
use App\Http\Requests\Stations\SubmitStationCaptureRequest;
use App\Http\Requests\Stations\UpdateStationRequest;
use App\Http\Resources\StationCameraResource;
use App\Http\Resources\StationResource;
use App\Models\ActivacionEstacion;
use App\Models\CamaraEstacion;
use App\Models\CuentaTecnica;
use App\Models\EstacionBiometrica;
use App\Models\EventoReconocimiento;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class StationController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()?->can('gestionar_dispositivos'), 403);

        return StationResource::collection(EstacionBiometrica::query()->latest()->paginate(min($request->integer('per_page', 20), 100)));
    }

    public function store(CreateStationRequest $request, AuditLogger $audit): JsonResponse
    {
        $station = DB::transaction(function () use ($request): EstacionBiometrica {
            $account = CuentaTecnica::create([
                'nombre' => 'Estación '.$request->string('name')->toString(),
                'tipo' => 'estacion_web',
                'token_hash' => hash('sha256', Str::random(80)),
                'scopes' => ['station:status', 'station:capture'],
                'activo' => true,
                'creado_por' => $request->user()->id,
            ]);

            return EstacionBiometrica::create([
                'codigo' => Str::slug($request->string('name')->toString()).'-'.Str::lower(Str::random(6)),
                'nombre' => $request->string('name')->toString(),
                'ubicacion' => $request->string('location')->toString(),
                'tipo_equipo' => 'pc',
                'cuenta_tecnica_id' => $account->id,
                'activo' => true,
                'configuracion' => ['mode' => $request->string('mode')->toString()],
            ]);
        });

        $audit->record($request, 'station.created', $request->user(), $station, newValues: ['mode' => $station->configuracion['mode'] ?? null]);

        return response()->json(['data' => new StationResource($station)], 201);
    }

    public function update(UpdateStationRequest $request, string $stationId, AuditLogger $audit): JsonResponse
    {
        $station = EstacionBiometrica::findOrFail($stationId);
        $old = $station->only(['nombre', 'ubicacion', 'activo', 'configuracion']);
        $configuration = $station->configuracion ?? [];
        if ($request->filled('mode')) {
            $configuration['mode'] = $request->string('mode')->toString();
        }
        $station->update([
            'nombre' => $request->input('name', $station->nombre),
            'ubicacion' => $request->input('location', $station->ubicacion),
            'activo' => $request->has('active') ? $request->boolean('active') : $station->activo,
            'configuracion' => $configuration,
        ]);
        $audit->record($request, 'station.updated', $request->user(), $station, $old, $station->only(['nombre', 'ubicacion', 'activo', 'configuracion']));

        return response()->json(['data' => new StationResource($station)]);
    }

    public function activationCode(Request $request, string $stationId, AuditLogger $audit): JsonResponse
    {
        abort_unless($request->user()?->can('gestionar_dispositivos'), 403);
        $station = EstacionBiometrica::findOrFail($stationId);
        $code = Str::upper(Str::random(10));

        $activation = ActivacionEstacion::create([
            'estacion_id' => $station->id,
            'codigo_hash' => Hash::make($code),
            'expira_en' => now()->addMinutes(10),
            'creado_por' => $request->user()->id,
        ]);

        $audit->record($request, 'station.activation_code_created', $request->user(), $station, newValues: ['activation_id' => $activation->id]);

        return response()->json(['data' => [
            'id' => $activation->id,
            'station_id' => $station->id,
            'activation_code' => $code,
            'expires_at' => $activation->expira_en->toISOString(),
        ]], 201);
    }

    public function activate(ActivateStationRequest $request, AuditLogger $audit): JsonResponse
    {
        $activation = ActivacionEstacion::query()
            ->with('estacion.cuentaTecnica')
            ->whereNull('usado_en')
            ->where('expira_en', '>', now())
            ->get()
            ->first(fn (ActivacionEstacion $candidate): bool => Hash::check($request->string('activation_code')->toString(), $candidate->codigo_hash));

        if ($activation === null || $activation->estacion === null || ! $activation->estacion->activo) {
            throw new ConflictHttpException('El código de activación no es válido, fue usado o expiró.');
        }

        [$station, $token] = DB::transaction(function () use ($request, $activation): array {
            $token = Str::random(80);
            $activation->update(['usado_en' => now()]);
            $activation->estacion->cuentaTecnica->update([
                'nombre' => $request->string('device_name')->toString(),
                'token_hash' => hash('sha256', $token),
                'token_rotado_en' => now(),
                'activo' => true,
            ]);
            $activation->estacion->update(['activado_en' => now(), 'revocado_en' => null, 'activo' => true]);

            return [$activation->estacion->refresh(), $token];
        });

        $audit->record($request, 'station.activated', null, $station, newValues: ['device_name' => $request->string('device_name')->toString()]);

        return response()->json(['data' => new StationResource($station)])
            ->withCookie($this->stationCookie($token));
    }

    public function session(Request $request): JsonResponse
    {
        return response()->json(['data' => new StationResource($request->attributes->get('station'))]);
    }

    public function capture(SubmitStationCaptureRequest $request): JsonResponse
    {
        $station = $request->attributes->get('station');
        $account = $request->attributes->get('technical_account');
        $camera = CamaraEstacion::whereKey($request->string('camera_id'))->where('estacion_id', $station->id)->firstOrFail();

        $event = EventoReconocimiento::create([
            'idempotency_key' => $request->header('Idempotency-Key'),
            'estacion_id' => $station->id,
            'camara_estacion_id' => $camera->id,
            'cuenta_tecnica_id' => $account->id,
            'tipo_persona' => 'desconocido',
            'confianza' => 0,
            'prueba_vida_superada' => false,
            'estado' => 'pendiente_revision',
            'motivo_estado' => 'captura_recibida_sin_procesar',
            'capturado_en' => $request->date('captured_at'),
            'recibido_en' => now(),
        ]);

        return response()->json(['data' => [
            'id' => $event->id,
            'station_id' => $station->id,
            'camera_id' => $camera->id,
            'status' => $event->estado,
            'captured_at' => $event->capturado_en->toISOString(),
        ]], 201);
    }

    public function revoke(ReasonRequest $request, string $stationId, AuditLogger $audit): JsonResponse
    {
        $station = EstacionBiometrica::with('cuentaTecnica')->findOrFail($stationId);
        DB::transaction(function () use ($station): void {
            $station->update(['activo' => false, 'revocado_en' => now()]);
            $station->cuentaTecnica?->update(['activo' => false, 'token_rotado_en' => now()]);
        });
        $audit->record($request, 'station.revoked', $request->user(), $station, newValues: ['reason' => 'redacted']);

        return response()->json(['data' => new StationResource($station->refresh())])
            ->withoutCookie('cienciasnet_station_session');
    }

    public function cameras(Request $request, string $stationId): JsonResponse
    {
        abort_unless($request->user()?->can('gestionar_dispositivos'), 403);
        $station = EstacionBiometrica::findOrFail($stationId);

        return response()->json(['data' => StationCameraResource::collection($station->camaras()->get())]);
    }

    public function storeCamera(CreateStationCameraRequest $request, string $stationId): JsonResponse
    {
        $station = EstacionBiometrica::findOrFail($stationId);
        $mode = match ($station->configuracion['mode'] ?? 'mixed') {
            'entry' => 'entrada',
            'exit' => 'salida',
            default => 'bidireccional',
        };
        $camera = $station->camaras()->create([
            'device_id_navegador' => $request->string('device_identifier')->toString(),
            'nombre' => $request->string('label')->toString(),
            'modo' => $mode,
            'activo' => $request->boolean('active', true),
        ]);

        return response()->json(['data' => new StationCameraResource($camera)], 201);
    }

    private function stationCookie(string $token): Cookie
    {
        return Cookie::create('cienciasnet_station_session', $token, 60 * 24 * 30, '/', null, false, true, false, 'Lax');
    }
}
