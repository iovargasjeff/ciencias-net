<?php

namespace App\Modules\Auth\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Modules\Usuarios\Infrastructure\Models\User;
use App\Support\AuditLogger;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordRecoveryController extends Controller
{
    public function requestLink(ForgotPasswordRequest $request, AuditLogger $audit): JsonResponse
    {
        $email = mb_strtolower($request->string('email')->toString());
        Password::sendResetLink(['email' => $email]);
        $audit->record($request, 'auth.password_recovery_requested', subject: $email);

        return response()->json([
            'data' => ['message' => 'Si el correo está registrado, enviaremos instrucciones de recuperación.'],
        ]);
    }

    public function reset(ResetPasswordRequest $request, AuditLogger $audit): JsonResponse
    {
        $status = Password::reset(
            $request->safe()->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) use ($request, $audit): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
                $audit->record($request, 'auth.password_reset', $user);
                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'error' => [
                    'code' => 'invalid_reset_request',
                    'message' => 'No fue posible restablecer la contraseña.',
                    'fields' => (object) [],
                ],
            ], 422);
        }

        return response()->json(['data' => ['message' => 'Contraseña actualizada.']]);
    }
}
