<?php

namespace App\Modules\Asistencia\Presentation\Middleware;

use App\Modules\Asistencia\Domain\Models\CuentaTecnica;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStationSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->cookie('cienciasnet_station_session')
            ?: $request->bearerToken();

        if (! is_string($token) || $token === '') {
            return $this->unauthenticated();
        }

        $account = CuentaTecnica::query()
            ->with('estacionBiometrica')
            ->where('token_hash', hash('sha256', $token))
            ->where('tipo', 'estacion_web')
            ->where('activo', true)
            ->first();

        $station = $account?->estacionBiometrica;

        if ($account === null || $station === null || ! $station->activo || $station->revocado_en !== null) {
            return $this->unauthenticated();
        }

        $scopes = $account->scopes ?? [];
        $required = $request->is('api/v1/station/captures') ? 'station:capture' : 'station:status';

        if (! in_array($required, $scopes, true)) {
            return response()->json([
                'error' => ['code' => 'forbidden', 'message' => 'La estación no tiene permiso para esta acción.', 'fields' => (object) []],
            ], 403);
        }

        $account->forceFill(['ultimo_contacto' => now()])->save();
        $station->forceFill(['ultimo_contacto' => now()])->save();

        $request->attributes->set('technical_account', $account);
        $request->attributes->set('station', $station);

        return $next($request);
    }

    private function unauthenticated(): Response
    {
        return response()->json([
            'error' => ['code' => 'unauthenticated', 'message' => 'Debes activar la estación.', 'fields' => (object) []],
        ], 401);
    }
}
