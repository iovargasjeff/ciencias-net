<?php

namespace App\Modules\Shared\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Shared\Application\PrivateFileService;
use App\Modules\Shared\Infrastructure\Models\PrivateFile;
use App\Modules\Shared\Presentation\Requests\UploadPrivateFileRequest;
use App\Modules\Shared\Presentation\Resources\PrivateFileResource;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PrivateFileController extends Controller
{
    public function store(UploadPrivateFileRequest $request, PrivateFileService $service): JsonResponse
    {
        $privateFile = $service->store($request, $request->file('file'), $request->validated(), $request->user());

        return response()->json(['data' => new PrivateFileResource($privateFile)], 201);
    }

    public function download(Request $request, string $fileId, PrivateFileService $service)
    {
        $privateFile = PrivateFile::findOrFail($fileId);
        Gate::authorize('view', $privateFile);

        if ($request->boolean('temporary')) {
            return response()->json(['data' => $service->temporaryDownloadUrl($request, $privateFile, $request->user())]);
        }

        return $this->downloadResponse($privateFile, $request, 'private_file.downloaded');
    }

    public function signedDownload(Request $request, string $fileId)
    {
        $privateFile = PrivateFile::findOrFail($fileId);
        abort_if($privateFile->isDeleted() || $privateFile->isExpired(), 404);

        return $this->downloadResponse($privateFile, $request, 'private_file.signed_downloaded');
    }

    private function downloadResponse(PrivateFile $privateFile, Request $request, string $auditAction)
    {
        abort_unless(Storage::disk($privateFile->disk)->exists($privateFile->path), 404);

        app(AuditLogger::class)->record($request, $auditAction, $request->user(), $privateFile, newValues: [
            'purpose' => $privateFile->purpose,
            'mime_type' => $privateFile->mime_type,
            'size_bytes' => $privateFile->size_bytes,
        ]);

        return Storage::disk($privateFile->disk)->download(
            $privateFile->path,
            Str::slug(pathinfo($privateFile->original_name, PATHINFO_FILENAME)).'.'.pathinfo($privateFile->original_name, PATHINFO_EXTENSION)
        );
    }
}
