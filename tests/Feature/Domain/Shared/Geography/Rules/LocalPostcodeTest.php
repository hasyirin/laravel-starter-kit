<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Models\Postcode;
use App\Domain\Shared\Geography\Models\PostOffice;
use App\Domain\Shared\Geography\Models\State;
use App\Domain\Shared\Geography\Rules\LocalPostcode;

beforeEach(function (): void {
    $johor = State::factory()->create(['name' => 'Johor']);
    $perlis = State::factory()->create(['name' => 'Perlis']);

    config()->set('address.locality', ['country' => $johor->country->code, 'state' => $johor->code]);

    Postcode::factory()->for(PostOffice::factory()->for($johor))->create(['code' => '84000']);
    Postcode::factory()->for(PostOffice::factory()->for($perlis))->create(['code' => '01000']);
});

test('a postcode of a local post office passes', function (): void {
    $validator = validator(['postcode' => '84000'], ['postcode' => [new LocalPostcode]]);

    expect($validator->passes())->toBeTrue();
});

test('a postcode outside the local area fails with the local-area error', function (): void {
    $validator = validator(['postcode' => '01000'], ['postcode' => [new LocalPostcode]]);

    expect($validator->errors()->get('postcode'))->toBe([__('common/inputs.postcode_outside_local_area')]);
});
