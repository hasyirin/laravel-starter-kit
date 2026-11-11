<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Models\Country;

beforeEach(function (): void {
    config()->set('address.locality', ['country' => 'MYS', 'state' => null, 'district' => null]);
});

test('the local country cannot be deleted, also outside the delete action', function (): void {
    $country = Country::factory()->create(['code' => 'MYS']);

    expect(fn () => $country->delete())->toThrow(LogicException::class, 'A country that is or contains the locality cannot be deleted.')
        ->and($country->fresh()->trashed())->toBeFalse();
});

test('the code of the local country cannot change, also outside the save action', function (): void {
    $country = Country::factory()->create(['code' => 'MYS']);

    expect(fn () => $country->update(['code' => 'MAS']))->toThrow(LogicException::class, 'The code of a country that is or contains the locality cannot change.')
        ->and($country->fresh()->code)->toBe('MYS');
});

test('a country outside the locality can take the locality code', function (): void {
    $country = Country::factory()->create(['code' => 'MAS']);

    $country->update(['code' => 'MYS']);

    expect($country->fresh()->code)->toBe('MYS');
});
