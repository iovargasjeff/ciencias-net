<?php

namespace App\Support\Biometrics;

use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Str;
use RuntimeException;

class BiometricEmbeddingEncryptor
{
    private Encrypter $encrypter;

    public function __construct()
    {
        $key = config('biometrics.embedding_key');

        if (! is_string($key) || $key === '') {
            throw new RuntimeException('BIOMETRIC_EMBEDDING_KEY must be configured.');
        }

        if (Str::startsWith($key, 'base64:')) {
            $key = base64_decode(substr($key, 7), true);
        }

        if (! is_string($key) || strlen($key) !== 32) {
            throw new RuntimeException('BIOMETRIC_EMBEDDING_KEY must be a 32-byte key or base64 encoded 32-byte key.');
        }

        $this->encrypter = new Encrypter($key, 'AES-256-CBC');
    }

    public function encrypt(string $embedding): string
    {
        return $this->encrypter->encryptString($embedding);
    }

    public function decrypt(string $encryptedEmbedding): string
    {
        return $this->encrypter->decryptString($encryptedEmbedding);
    }
}
