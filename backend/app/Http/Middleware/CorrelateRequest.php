<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CorrelateRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $this->requestId($request);
        $request->attributes->set('request_id', $requestId);
        Log::withContext([
            'request_id' => $requestId,
            'path' => $request->path(),
        ]);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }

    private function requestId(Request $request): string
    {
        $provided = $request->header('X-Request-Id');

        if (is_string($provided) && preg_match('/^[A-Za-z0-9._:-]{8,80}$/', $provided) === 1) {
            return $provided;
        }

        return (string) Str::uuid();
    }
}
