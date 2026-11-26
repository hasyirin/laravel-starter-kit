<?php

declare(strict_types=1);

test('passkeys allow only the https origin with the host and port of the app url', function (string $url, string $origin): void {
    config(['app.url' => $url]);

    $fortify = require config_path('fortify.php');

    expect($fortify['passkeys']['allowed_origins'])->toBe([$origin]);
})->with([
    'an http url' => ['http://kit.test', 'https://kit.test'],
    'an http url with a port' => ['http://kit.test:8443', 'https://kit.test:8443'],
    'an http url with a path' => ['http://kit.test/sub/', 'https://kit.test'],
    'an https url' => ['https://kit.test', 'https://kit.test'],
]);

test('passkeys sign in on the Fortify guard', function (): void {
    expect(config('passkeys.guard'))->toBe(config('fortify.guard'));
});
