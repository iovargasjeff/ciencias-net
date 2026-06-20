<?php

namespace App\Modules\Shared\Application;

use App\Modules\Shared\Infrastructure\Models\PrivateFile;
use App\Modules\Usuarios\Infrastructure\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PrivateFileService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function store(Request $request, UploadedFile $file, array $data, User $uploader): PrivateFile
    {
        $checksum = hash_file('sha256', $file->getRealPath());
        $providedChecksum = isset($data['checksum_sha256']) ? mb_strtolower((string) $data['checksum_sha256']) : null;

        if ($providedChecksum !== null && $providedChecksum !== $checksum) {
            throw ValidationException::withMessages([
                'checksum_sha256' => ['El checksum SHA-256 no coincide con el archivo recibido.'],
            ]);
        }

        if (($data['purpose'] ?? null) === 'biometric_exception' && empty($data['expires_at'])) {
            throw ValidationException::withMessages([
                'expires_at' => ['La evidencia biométrica excepcional requiere fecha de expiración.'],
            ]);
        }

        $disk = config('filesystems.private_files_disk', env('PRIVATE_FILES_DISK', 'private'));
        $extension = $file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin';
        $path = trim($data['purpose'], '/').'/'.now()->format('Y/m/d').'/'.Str::uuid().'.'.$extension;

        return DB::transaction(function () use ($request, $file, $data, $uploader, $checksum, $disk, $path): PrivateFile {
            Storage::disk($disk)->putFileAs(dirname($path), $file, basename($path), ['visibility' => 'private']);

            $privateFile = PrivateFile::create([
                'purpose' => $data['purpose'],
                'disk' => $disk,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                'size_bytes' => $file->getSize(),
                'checksum_sha256' => $checksum,
                'metadata' => $data['metadata'] ?? null,
                'uploaded_by' => $uploader->id,
                'expires_at' => $data['expires_at'] ?? null,
            ]);

            $this->audit->record($request, 'private_file.uploaded', $uploader, $privateFile, newValues: [
                'purpose' => $privateFile->purpose,
                'mime_type' => $privateFile->mime_type,
                'size_bytes' => $privateFile->size_bytes,
                'checksum_sha256' => $privateFile->checksum_sha256,
                'expires_at' => $privateFile->expires_at?->toIso8601String(),
            ]);

            return $privateFile;
        });
    }

    public function temporaryDownloadUrl(Request $request, PrivateFile $privateFile, User $actor): array
    {
        $expiresAt = now()->addMinutes((int) config('filesystems.private_files_url_ttl_minutes', 5));
        $url = URL::temporarySignedRoute('api.v1.private-files.signed-download', $expiresAt, [
            'fileId' => $privateFile->id,
        ]);

        $this->audit->record($request, 'private_file.temporary_url_created', $actor, $privateFile, newValues: [
            'expires_at' => $expiresAt->toIso8601String(),
        ]);

        return ['url' => $url, 'expires_at' => $expiresAt->toIso8601String()];
    }

    public function deleteExpired(?Request $request = null): int
    {
        $expiredFiles = PrivateFile::query()
            ->whereNull('deleted_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        foreach ($expiredFiles as $privateFile) {
            Storage::disk($privateFile->disk)->delete($privateFile->path);
            $privateFile->forceFill(['deleted_at' => now()])->save();
            $this->audit->record($request, 'private_file.deleted_by_retention', subject: $privateFile, newValues: [
                'purpose' => $privateFile->purpose,
                'expired_at' => $privateFile->expires_at?->toIso8601String(),
            ]);
        }

        return $expiredFiles->count();
    }
}
