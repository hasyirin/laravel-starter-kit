<?php

declare(strict_types=1);

use App\Domain\Shared\Api\Support\MaskedBody;

beforeEach(function (): void {
    config([
        'system.api_log.masked_keys' => ['password', 'token', 'secret', 'authorization', 'apikey'],
        'system.api_log.body_limit' => 1_000,
    ]);
});

test('a JSON body hides the value of each key that contains a masked key, at any depth and in any case', function (): void {
    $body = json_encode([
        'name' => 'Ali',
        'Password' => 'hunter2',
        'access_token' => 'abc',
        'client' => ['client_secret' => 'xyz', 'api-key' => 'k1', 'apiKey' => 'k2', 'id' => 7],
        'items' => [['token' => 'nested'], ['note' => 'kept']],
    ]);

    expect(json_decode((string) MaskedBody::of($body), true))->toBe([
        'name' => 'Ali',
        'Password' => '[hidden]',
        'access_token' => '[hidden]',
        'client' => ['client_secret' => '[hidden]', 'api-key' => '[hidden]', 'apiKey' => '[hidden]', 'id' => 7],
        'items' => [['token' => '[hidden]'], ['note' => 'kept']],
    ]);
});

test('a body that is not JSON stays as it is', function (): void {
    expect(MaskedBody::of('plain text'))->toBe('plain text');
});

test('an empty JSON object stays an object', function (): void {
    expect(MaskedBody::of('{"filters":{},"password":"x"}'))->toBe('{"filters":{},"password":"[hidden]"}');
});

test('a number keeps its zero fraction', function (): void {
    expect(MaskedBody::of('{"total":1.0,"password":"x"}'))->toBe('{"total":1.0,"password":"[hidden]"}');
});

test('a big integer keeps all its digits as a string', function (): void {
    expect(MaskedBody::of('{"id":12345678901234567890,"password":"x"}'))->toBe('{"id":"12345678901234567890","password":"[hidden]"}');
});

test('a body that starts like JSON but cannot be decoded stores nothing', function (string $body): void {
    expect(MaskedBody::of($body))->toBeNull();
})->with([
    'nested too deep' => [' '.str_repeat('[', 600).'{"password":"s3cret"}'.str_repeat(']', 600)],
    'byte that is not UTF-8' => ["{\"password\":\"s3cret\",\"note\":\"\xff\"}"],
]);

test('a body longer than the limit is cut to the limit', function (): void {
    config(['system.api_log.body_limit' => 10]);

    expect(MaskedBody::of(str_repeat('a', 25)))->toBe(str_repeat('a', 10));
});

test('the cut never splits a character', function (): void {
    config(['system.api_log.body_limit' => 4]);

    expect(MaskedBody::of('ab日本'))->toBe('ab');
});

test('the mask runs before the cut, so a cut cannot leave a masked value', function (): void {
    config(['system.api_log.body_limit' => 30]);

    expect(MaskedBody::of(json_encode(['password' => str_repeat('s', 50)])))->toBe('{"password":"[hidden]"}');
});

test('an empty or missing body stores nothing', function (?string $body): void {
    expect(MaskedBody::of($body))->toBeNull();
})->with(['missing' => [null], 'empty' => ['']]);
