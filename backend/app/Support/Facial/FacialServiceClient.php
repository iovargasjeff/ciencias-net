<?php

namespace App\Support\Facial;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FacialServiceClient
{
    public function health(): array
    {
        return $this->request()
            ->get($this->url('/health'))
            ->throw()
            ->json();
    }

    /**
     * @param  array<int, UploadedFile>  $images
     * @return array{embedding:string, quality:float, liveness:float, model_version:string}
     */
    public function createEmbedding(array $images): array
    {
        $request = $this->request();

        foreach ($images as $image) {
            $request = $request->attach(
                'images',
                file_get_contents($image->getRealPath()),
                $image->getClientOriginalName(),
                ['Content-Type' => $image->getMimeType() ?: 'application/octet-stream']
            );
        }

        return $this->send(fn () => $request->post($this->url('/v1/enrollments')));
    }

    /**
     * @param  array<int, array{id:string, embedding:string}>  $candidates
     * @return array{matched:bool, candidate_id:?string, confidence:float, quality:float, liveness:float, model_version:string}
     */
    public function identify(UploadedFile $image, array $candidates, string $idempotencyKey): array
    {
        $request = $this->request()
            ->withHeader('Idempotency-Key', $idempotencyKey)
            ->attach(
                'image',
                file_get_contents($image->getRealPath()),
                $image->getClientOriginalName(),
                ['Content-Type' => $image->getMimeType() ?: 'application/octet-stream']
            );

        return $this->send(fn () => $request->post($this->url('/v1/identifications'), [
            'candidates' => json_encode($candidates, JSON_THROW_ON_ERROR),
        ]));
    }

    private function request()
    {
        $token = config('facial-service.token');

        if (! is_string($token) || $token === '') {
            throw new RuntimeException('FACIAL_SERVICE_TOKEN must be configured.');
        }

        return Http::timeout((float) config('facial-service.timeout', 5))
            ->acceptJson()
            ->withHeader('X-Facial-Service-Token', $token);
    }

    private function send(callable $callback): array
    {
        try {
            return $callback()->throw()->json();
        } catch (ConnectionException $exception) {
            throw new FacialServiceUnavailable(previous: $exception);
        }
    }

    private function url(string $path): string
    {
        return rtrim((string) config('facial-service.url'), '/').$path;
    }
}
