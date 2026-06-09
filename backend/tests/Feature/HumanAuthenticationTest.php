<?php

use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

it('creates a cookie session and returns the authenticated human', function () {
    $user = User::factory()->create(['email' => 'active@example.test', 'password' => 'correct-password']);

    $this->withSession(['_token' => 'csrf-token'])
        ->withHeaders(['Origin' => 'http://localhost:5173', 'X-CSRF-TOKEN' => 'csrf-token'])
        ->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'correct-password'])
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonMissing(['token']);

    $this->getJson('/api/v1/auth/session')
        ->assertOk()
        ->assertJsonPath('data.email', $user->email);

    $this->assertDatabaseHas('audit_logs', ['user_id' => $user->id, 'action' => 'auth.login_succeeded']);
});

it('logs out and invalidates the session', function () {
    $user = User::factory()->create(['password' => 'correct-password']);

    $this->withSession(['_token' => 'csrf-token'])
        ->withHeaders(['Origin' => 'http://localhost:5173', 'X-CSRF-TOKEN' => 'csrf-token'])
        ->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'correct-password'])
        ->assertOk();

    $this->withHeaders(['Origin' => 'http://localhost:5173', 'X-CSRF-TOKEN' => csrf_token()])
        ->postJson('/api/v1/auth/logout')
        ->assertOk()
        ->assertJsonPath('data.logged_out', true);

    $this->assertGuest();
    $this->assertDatabaseHas('audit_logs', ['user_id' => $user->id, 'action' => 'auth.logout']);
});

it('rejects disabled accounts with a generic response', function () {
    $user = User::factory()->create(['activo' => false, 'password' => 'correct-password']);

    $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'correct-password'])
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'invalid_credentials');

    $this->actingAs($user)
        ->getJson('/api/v1/auth/session')
        ->assertUnauthorized()
        ->assertJsonPath('error.code', 'unauthenticated');
});

it('does not reveal whether a recovery email exists', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'known@example.test']);

    $known = $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])->assertOk();
    $unknown = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'unknown@example.test'])->assertOk();

    expect($known->json('data.message'))->toBe($unknown->json('data.message'));
    Notification::assertSentTo($user, ResetPassword::class);
});

it('limits repeated login attempts', function () {
    User::factory()->create(['email' => 'limited@example.test']);

    foreach (range(1, 5) as $_) {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'limited@example.test',
            'password' => 'wrong-password',
        ])->assertUnprocessable();
    }

    $this->postJson('/api/v1/auth/login', [
        'email' => 'limited@example.test',
        'password' => 'wrong-password',
    ])->assertTooManyRequests();
});

it('publishes the Sanctum CSRF cookie endpoint', function () {
    $this->get('/sanctum/csrf-cookie')
        ->assertNoContent()
        ->assertCookie('XSRF-TOKEN');
});

it('formats csrf token mismatches as api errors', function () {
    Route::post('/api/v1/test-csrf-token-mismatch', function (): void {
        throw new TokenMismatchException('CSRF token mismatch.');
    });

    $this->postJson('/api/v1/test-csrf-token-mismatch')
        ->assertStatus(419)
        ->assertJsonPath('error.code', 'csrf_token_mismatch');
});
