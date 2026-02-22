<?php

use App\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

/*
|--------------------------------------------------------------------------
| Authentication Tests
|--------------------------------------------------------------------------
|
| Covers: registration, login, logout, profile access, and token refresh
| via the Dingo API v1 endpoints at /api/auth/*.
|
*/

// ── Helpers ───────────────────────────────────────────────────────────────

function jwtHeaders(User $user): array
{
    $token = JWTAuth::fromUser($user);

    return [
        'Authorization' => "Bearer {$token}",
        'Accept'        => 'application/json',
    ];
}

// ── Registration ──────────────────────────────────────────────────────────

it('registers a new user with valid data', function () {
    $response = $this->postJson('/api/auth/register', [
        'name'     => 'Test User',
        'email'    => 'newuser@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(201)
        ->assertJson(['success' => true])
        ->assertJsonStructure([
            'success',
            'data' => ['user', 'access_token', 'token_type', 'expires_in'],
        ]);

    $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
});

it('rejects registration when required fields are missing', function () {
    $response = $this->postJson('/api/auth/register', [
        'email' => 'incomplete@example.com',
    ]);

    $response->assertStatus(422);
});

it('rejects registration with a duplicate email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $response = $this->postJson('/api/auth/register', [
        'name'     => 'Duplicate User',
        'email'    => 'taken@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(422);
});

it('rejects registration with an invalid email format', function () {
    $response = $this->postJson('/api/auth/register', [
        'name'     => 'Bad Email',
        'email'    => 'not-an-email',
        'password' => 'password123',
    ]);

    $response->assertStatus(422);
});

it('rejects registration when password is too short', function () {
    $response = $this->postJson('/api/auth/register', [
        'name'     => 'Short Pass',
        'email'    => 'short@example.com',
        'password' => 'abc',
    ]);

    $response->assertStatus(422);
});

// ── Login ─────────────────────────────────────────────────────────────────

it('logs in a user with valid credentials', function () {
    User::factory()->create([
        'email'    => 'login@example.com',
        'password' => bcrypt('correcthorse'),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email'    => 'login@example.com',
        'password' => 'correcthorse',
    ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true])
        ->assertJsonStructure([
            'success',
            'data' => ['user', 'access_token', 'token_type', 'expires_in'],
        ]);
});

it('rejects login with wrong password', function () {
    User::factory()->create([
        'email'    => 'wrong@example.com',
        'password' => bcrypt('correct'),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email'    => 'wrong@example.com',
        'password' => 'incorrect',
    ]);

    $response->assertStatus(401);
});

it('rejects login with non-existent email', function () {
    $response = $this->postJson('/api/auth/login', [
        'email'    => 'ghost@example.com',
        'password' => 'anything',
    ]);

    $response->assertStatus(401);
});

it('rejects login when email and password are missing', function () {
    $response = $this->postJson('/api/auth/login', []);

    $response->assertStatus(422);
});

// ── Authenticated Profile ─────────────────────────────────────────────────

it('returns the authenticated user profile', function () {
    $user = User::factory()->create(['email' => 'me@example.com']);

    $response = $this->getJson('/api/user', jwtHeaders($user));

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data'    => ['user' => ['email' => 'me@example.com']],
        ]);
});

it('returns 401 when accessing profile without authentication', function () {
    $response = $this->getJson('/api/user', ['Accept' => 'application/json']);

    $response->assertStatus(401);
});

// ── Logout ────────────────────────────────────────────────────────────────

it('allows an authenticated user to logout', function () {
    $user = User::factory()->create();

    $response = $this->postJson('/api/auth/logout', [], jwtHeaders($user));

    $response->assertStatus(200)
        ->assertJson(['success' => true]);
});

it('returns 401 when logging out without a token', function () {
    $response = $this->postJson('/api/auth/logout', [], [
        'Accept' => 'application/json',
    ]);

    $response->assertStatus(401);
});

// ── Token Refresh ─────────────────────────────────────────────────────────

it('refreshes a valid JWT token', function () {
    $user = User::factory()->create();

    $response = $this->postJson('/api/auth/refresh', [], jwtHeaders($user));

    $response->assertStatus(200)
        ->assertJson(['success' => true])
        ->assertJsonStructure([
            'success',
            'data' => ['access_token', 'token_type', 'expires_in'],
        ]);
});

it('returns 401 when refreshing without a token', function () {
    $response = $this->postJson('/api/auth/refresh', [], [
        'Accept' => 'application/json',
    ]);

    $response->assertStatus(401);
});
