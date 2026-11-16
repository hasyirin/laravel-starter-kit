<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    Route::middleware('api')->prefix('api/v1')->group(function (): void {
        Route::post('notes', fn (): array => request()->validate(['title' => ['required', 'string']]));
        Route::get('failure', fn (): never => throw new RuntimeException('Database password is hunter2'));
        Route::get('denied', fn () => abort(403, 'Only the owner can see this note.'));
    });
});

test('a validation error returns 422 as a problem with the errors', function (): void {
    $response = $this->withToken(createApiToken())->postJson('/api/v1/notes');

    $response->assertUnprocessable()
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertExactJson([
            'type' => 'about:blank',
            'title' => 'Unprocessable Content',
            'status' => 422,
            'detail' => 'The request has data that is not valid.',
            'trace_id' => $response->headers->get('X-Request-Id'),
            'errors' => ['title' => ['The title field is required.']],
        ]);
});

test('an unknown API path returns 404 as a problem', function (): void {
    $response = $this->getJson('/api/v1/missing');

    $response->assertNotFound()
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJsonPath('title', 'Not Found')
        ->assertJsonPath('detail', 'The resource does not exist.');
});

test('an API path that is not valid UTF-8 returns 400 as a problem', function (): void {
    $response = $this->get('/api/v1/%ff');

    $response->assertBadRequest()
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJsonPath('status', 400);
});

test('an API path with percent-encoded letters returns 404 as a problem', function (): void {
    $response = $this->get('/%61pi/v1/missing');

    $response->assertNotFound()->assertHeader('Content-Type', 'application/problem+json');
});

test('a wrong method returns 405 as a problem with the allowed methods', function (): void {
    $response = $this->deleteJson('/api/v1/ping');

    $response->assertMethodNotAllowed()
        ->assertHeader('Allow', 'GET, HEAD')
        ->assertJsonPath('detail', 'The resource does not accept this method.');
});

test('an HTTP error keeps its status and does not show its message', function (): void {
    $response = $this->withToken(createApiToken())->getJson('/api/v1/denied');

    $response->assertForbidden()->assertJsonPath('detail', 'The API client cannot make this request.');
});

test('an unhandled exception returns 500 without internals when debug mode is off', function (): void {
    config(['app.debug' => false]);

    $response = $this->withToken(createApiToken())->getJson('/api/v1/failure');

    $response->assertInternalServerError()
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertExactJson([
            'type' => 'about:blank',
            'title' => 'Internal Server Error',
            'status' => 500,
            'detail' => 'The server could not complete the request.',
            'trace_id' => $response->headers->get('X-Request-Id'),
        ]);
});

test('an unhandled exception shows its message when debug mode is on', function (): void {
    config(['app.debug' => true]);

    $response = $this->withToken(createApiToken())->getJson('/api/v1/failure');

    $response->assertInternalServerError()->assertJsonPath('detail', 'Database password is hunter2');
});

test('a web route keeps its own error page', function (): void {
    $response = $this->get('/missing-page');

    $response->assertNotFound();

    expect($response->headers->get('Content-Type'))->toContain('text/html');
});
