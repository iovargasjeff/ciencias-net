<?php

namespace App\Modules\Auth\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\AuthUserResource;
use App\Modules\Usuarios\Infrastructure\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionController extends Controller
{
    public function store(LoginRequest $request, AuditLogger $audit): JsonResponse
    {
        $credentials = [
            'email' => mb_strtolower($request->string('email')->toString()),
            'password' => $request->string('password')->toString(),
            'activo' => true,
        ];

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            $audit->record($request, 'auth.login_failed', subject: $credentials['email']);

            return response()->json([
                'error' => [
                    'code' => 'invalid_credentials',
                    'message' => 'Las credenciales no son válidas.',
                    'fields' => (object) [],
                ],
            ], 422);
        }

        $request->session()->regenerate();

        /** @var User $user */
        $user = $request->user();
        $user->forceFill(['ultimo_login' => now()])->save();
        $audit->record($request, 'auth.login_succeeded', $user);

        return response()->json(['data' => new AuthUserResource($user->load('roles'))]);
    }

    public function show(Request $request): JsonResponse
    {
        return response()->json(['data' => new AuthUserResource($request->user()->load('roles'))]);
    }

    public function destroy(Request $request, AuditLogger $audit): JsonResponse
    {
        $audit->record($request, 'auth.logout', $request->user());
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Auth::forgetGuards();

        return response()->json(['data' => ['logged_out' => true]]);
    }
}
