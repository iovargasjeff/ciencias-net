<?php

return [
    'embedding_key' => env('BIOMETRIC_EMBEDDING_KEY'),
    'storage_disk' => env('BIOMETRIC_STORAGE_DISK', env('FILESYSTEM_DISK', 'local')),
    'storage_prefix' => env('BIOMETRIC_STORAGE_PREFIX', 'private/biometrics'),
    'consent_document_version' => env('BIOMETRIC_CONSENT_DOCUMENT_VERSION', 'v1'),
];
