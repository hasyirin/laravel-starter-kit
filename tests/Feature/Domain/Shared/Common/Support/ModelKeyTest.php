<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Support\ModelKey;
use Tests\Fixtures\Models\Item;

test('a positive integer that a bigint holds is a valid integer key', function (int|string $value): void {
    expect(ModelKey::valid(new Item, $value))->toBeTrue();
})->with([
    'an integer' => [5],
    'digits' => ['5'],
    'the largest bigint' => ['9223372036854775807'],
    'a leading space, which PostgreSQL also reads as 5' => [' 5'],
    'a plus sign, which PostgreSQL also reads as 5' => ['+5'],
]);

test('any other value is not a valid integer key', function (string $value): void {
    expect(ModelKey::valid(new Item, $value))->toBeFalse();
})->with([
    'letters' => ['abc'],
    'more digits than a bigint holds' => ['99999999999999999999'],
    'one above the largest bigint' => ['9223372036854775808'],
    'zero' => ['0'],
    'a negative number' => ['-1'],
    'a decimal' => ['1.5'],
    'an empty string' => [''],
    'a leading zero' => ['05'],
]);

test('any value is a valid string key', function (): void {
    expect(ModelKey::valid((new Item)->setKeyType('string'), 'abc'))->toBeTrue();
});
