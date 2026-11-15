<?php

declare(strict_types=1);

use App\Domain\Shared\Api\Support\ApiTokenFormat;

beforeEach(function (): void {
    config(['system.api.token_prefix' => 'kit']);
});

test('a new API token has the prefix, 40 random characters and a checksum of 6 characters', function (): void {
    $token = ApiTokenFormat::generate();

    expect($token)->toMatch('/\Akit_[0-9A-Za-z]{46}\z/')
        ->and(ApiTokenFormat::valid($token))->toBeTrue()
        ->and(ApiTokenFormat::generate())->not->toBe($token);
});

test('a token with the right checksum is valid', function (): void {
    expect(ApiTokenFormat::valid('kit_abcdefghijABCDEFGHIJ0123456789klmnopqrst2ywciD'))->toBeTrue();
});

test('a token that is mistyped, has another prefix or has the wrong form is not valid', function (string $token): void {
    expect(ApiTokenFormat::valid($token))->toBeFalse();
})->with([
    'a mistyped character' => ['kit_abcdefghijABCDEFGHIJ0123456789klmnopqrsT2ywciD'],
    'a wrong checksum' => ['kit_abcdefghijABCDEFGHIJ0123456789klmnopqrst2ywciE'],
    'another prefix' => ['abc_abcdefghijABCDEFGHIJ0123456789klmnopqrst2ywciD'],
    'no prefix' => ['abcdefghijABCDEFGHIJ0123456789klmnopqrst2ywciD'],
    'one character short' => ['kit_abcdefghijABCDEFGHIJ0123456789klmnopqrs2ywciD'],
    'a character outside base62' => ['kit_abcdefghijABCDEFGHIJ0123456789klmnopqr-t2ywciD'],
    'an empty string' => [''],
]);

test('the app stores the SHA-256 hash and the last 4 characters of a token', function (): void {
    $token = 'kit_abcdefghijABCDEFGHIJ0123456789klmnopqrst2ywciD';

    expect(ApiTokenFormat::hash($token))->toBe('85284dd5ee419a8225f3be2eecc68520eb0f81ca136775e3d7755591888fc1db')
        ->and(ApiTokenFormat::lastFour($token))->toBe('wciD');
});
