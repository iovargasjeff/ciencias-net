<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureIdempotentRequest;
use App\Http\Resources\PaginatedCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Route;

it('returns the versioned health contract', function () {
    $this->getJson('/api/v1/health')
        ->assertOk()
        ->assertJsonPath('data.status', 'ok')
        ->assertJsonStructure(['data' => ['status', 'checks' => ['api', 'queue', 'cache']]]);
});

it('returns stable validation errors', function () {
    Route::post('/api/v1/test-validation', function () {
        request()->validate(['name' => ['required', 'string']]);
    });

    $this->postJson('/api/v1/test-validation')
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'validation_failed')
        ->assertJsonStructure(['error' => ['code', 'message', 'fields' => ['name']]]);
});

it('returns data and pagination metadata', function () {
    $paginator = new LengthAwarePaginator([['id' => 1]], 3, 1, 1, [
        'path' => '/api/v1/items',
    ]);

    $response = PaginatedCollection::make($paginator);

    expect($response->getData(true))
        ->toHaveKeys(['data', 'meta', 'links'])
        ->and($response->getData(true)['meta']['total'])->toBe(3);
});

it('replays a successful idempotent request', function () {
    $calls = 0;
    Route::post('/api/v1/test-idempotency', function () use (&$calls) {
        $calls++;

        return response()->json(['calls' => $calls], 201);
    })->middleware(EnsureIdempotentRequest::class);

    $headers = ['Idempotency-Key' => 'same-operation'];

    $this->postJson('/api/v1/test-idempotency', [], $headers)->assertCreated()->assertJson(['calls' => 1]);
    $this->postJson('/api/v1/test-idempotency', [], $headers)->assertCreated()->assertJson(['calls' => 1]);
});
