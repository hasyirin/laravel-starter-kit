<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Support\ModelKey;
use Illuminate\Database\Eloquent\Model;
use Tests\Fixtures\Models\Crate;
use Tests\Fixtures\Models\Item;
use Tests\Fixtures\Models\Parcel;
use Tests\Fixtures\Models\Survey;

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

test('a uuid is a valid uuid key', function (): void {
    expect(ModelKey::valid(new Parcel, '0198a1b2-c3d4-7e5f-8a9b-0c1d2e3f4a5b'))->toBeTrue();
});

test('any other value is not a valid uuid key', function (mixed $value): void {
    expect(ModelKey::valid(new Parcel, $value))->toBeFalse();
})->with([
    'letters' => ['abc'],
    'an empty string' => [''],
    'one character short of a uuid' => ['0198a1b2-c3d4-7e5f-8a9b-0c1d2e3f4a5'],
    'an integer' => [5],
    'an array' => [['0198a1b2-c3d4-7e5f-8a9b-0c1d2e3f4a5b']],
]);

test('a ulid is a valid ulid key', function (): void {
    expect(ModelKey::valid(new Crate, '01ARZ3NDEKTSV4RRFFQ69G5FAV'))->toBeTrue();
});

test('any other value is not a valid ulid key', function (mixed $value): void {
    expect(ModelKey::valid(new Crate, $value))->toBeFalse();
})->with([
    'letters' => ['abc'],
    'an empty string' => [''],
    'one character short of a ulid' => ['01ARZ3NDEKTSV4RRFFQ69G5FA'],
    'an integer' => [5],
    'an array' => [['01ARZ3NDEKTSV4RRFFQ69G5FAV']],
]);

test('any value is a valid key of a model whose unique id column is not its key', function (): void {
    expect(ModelKey::valid((new Survey)->setKeyType('string'), 'abc'))->toBeTrue();
});

test('a unique id column beside an integer key keeps the integer rule', function (): void {
    expect(ModelKey::valid(new Survey, 5))->toBeTrue()
        ->and(ModelKey::valid(new Survey, '0198a1b2-c3d4-7e5f-8a9b-0c1d2e3f4a5b'))->toBeFalse();
});

test('an integer check accepts only a positive integer that a bigint holds', function (mixed $value, bool $integer): void {
    expect(ModelKey::isInteger($value))->toBe($integer);
})->with([
    'an integer' => [5, true],
    'digits' => ['5', true],
    'letters' => ['abc', false],
    'more digits than a bigint holds' => ['99999999999999999999', false],
    'zero' => [0, false],
    'an array' => [[5], false],
]);

test('the absent key is a well formed key of the key type', function (Model $model, int|string $absent): void {
    expect(ModelKey::absentKey($model))->toBe($absent);
})->with([
    'an integer key' => [fn (): Model => new Item, 0],
    'a uuid key' => [fn (): Model => new Parcel, '00000000-0000-0000-0000-000000000000'],
    'a ulid key' => [fn (): Model => new Crate, '00000000000000000000000000'],
    'a unique id column beside an integer key' => [fn (): Model => new Survey, 0],
]);

test('the absent key of a uuid or ulid model passes the key check', function (Model $model): void {
    expect(ModelKey::valid($model, ModelKey::absentKey($model)))->toBeTrue();
})->with([
    'a uuid key' => [fn (): Model => new Parcel],
    'a ulid key' => [fn (): Model => new Crate],
]);
