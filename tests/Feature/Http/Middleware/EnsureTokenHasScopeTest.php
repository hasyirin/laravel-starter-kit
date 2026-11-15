<?php

declare(strict_types=1);

use App\Domain\Shared\Api\Enums\ApiCallOutcome;
use App\Domain\Shared\Api\Models\ApiCall;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    Route::middleware('api')->prefix('api/v1')->group(function (): void {
        Route::get('notes', fn (): array => ['notes' => []])->middleware('scope:notes.read');
        Route::post('notes', fn (): array => ['saved' => true])->middleware('scope:notes.read,notes.write');
    });
});

test('a token with the scope of the route passes', function (): void {
    $token = createApiToken(state: ['scopes' => ['notes.read']]);

    $this->withToken($token)->getJson('/api/v1/notes')->assertOk();
});

test('a token without the scope of the route returns 403 with a generic problem', function (): void {
    $token = createApiToken(state: ['scopes' => ['letters.read']]);

    $response = $this->withToken($token)->getJson('/api/v1/notes');

    $response->assertForbidden()
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJsonPath('detail', 'The API client cannot make this request.');
    expect(ApiCall::query()->sole()->outcome)->toBe(ApiCallOutcome::MissingScope);
});

test('a route with several scopes needs all of them', function (array $scopes, int $status): void {
    $token = createApiToken(state: ['scopes' => $scopes]);

    $this->withToken($token)->postJson('/api/v1/notes')->assertStatus($status);
})->with([
    'one of two' => [['notes.read'], 403],
    'both' => [['notes.read', 'notes.write'], 200],
]);
