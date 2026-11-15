<?php

declare(strict_types=1);

test('the ping endpoint answers a valid token that has no scope', function (): void {
    $token = createApiToken();

    $response = $this->withToken($token)->getJson(route('api.v1.ping'));

    $response->assertOk()->assertExactJson(['status' => 'ok']);
    expect(route('api.v1.ping', absolute: false))->toBe('/api/v1/ping');
});

test('the API sends no CORS headers to a preflight or a call from a browser origin', function (): void {
    $token = createApiToken();

    $preflight = $this->call('OPTIONS', '/api/v1/ping', server: [
        'HTTP_ORIGIN' => 'https://example.com',
        'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
    ]);
    $call = $this->withToken($token)->withHeader('Origin', 'https://example.com')->getJson(route('api.v1.ping'));

    $preflight->assertHeaderMissing('Access-Control-Allow-Origin');
    $call->assertOk()->assertHeaderMissing('Access-Control-Allow-Origin');
});
