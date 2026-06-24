<?php

namespace App\Support;

class SensitiveDataRedactor
{
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'token',
        'remember_token',
        'access_token',
        'refresh_token',
        'authorization',
        'cookie',
        'embedding',
        'embedding_cifrado',
        'biometric',
        'biometria',
        'nota_privada',
        'notas_privadas',
        'private_notes',
        'secret',
        'api_key',
        'file',
        'photo',
        'image',
    ];

    public static function redact(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $redacted = [];
        foreach ($value as $key => $item) {
            if (self::isSensitiveKey((string) $key)) {
                $redacted[$key] = '[REDACTED]';

                continue;
            }

            $redacted[$key] = is_array($item) ? self::redact($item) : $item;
        }

        return $redacted;
    }

    private static function isSensitiveKey(string $key): bool
    {
        $normalized = mb_strtolower($key);

        foreach (self::SENSITIVE_KEYS as $sensitiveKey) {
            if (str_contains($normalized, $sensitiveKey)) {
                return true;
            }
        }

        return false;
    }
}
