<?php

declare(strict_types=1);

test('the ping endpoint answers a valid token that has no scope', function (): void {
    $token = createApiToken();

    $response = $this->withToken($token)->getJson(route('api.v1.ping'));

    $response->assertOk()->assertExactJson(['status' => 'ok']);
    expect(route('api.v1.ping', absolute: false))->toBe('/api/v1/ping');
});
