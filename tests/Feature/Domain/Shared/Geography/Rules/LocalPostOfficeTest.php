<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Models\PostOffice;
use App\Domain\Shared\Geography\Models\State;
use App\Domain\Shared\Geography\Rules\LocalPostOffice;

beforeEach(function (): void {
    $johor = State::factory()->create(['name' => 'Johor']);
    $perlis = State::factory()->create(['name' => 'Perlis']);

    config()->set('address.locality', ['country' => $johor->country->code, 'state' => $johor->code]);

    $this->muar = PostOffice::factory()->for($johor)->create(['name' => 'Muar']);
    $this->kangar = PostOffice::factory()->for($perlis)->create(['name' => 'Kangar']);
});

test('a local post office passes', function (): void {
    $validator = validator(['post_office_id' => $this->muar->id], ['post_office_id' => [new LocalPostOffice]]);

    expect($validator->passes())->toBeTrue();
});

test('a post office outside the local area fails with the local-area error', function (): void {
    $validator = validator(['post_office_id' => $this->kangar->id], ['post_office_id' => [new LocalPostOffice]]);

    expect($validator->errors()->get('post_office_id'))->toBe([__('common/inputs.city_outside_local_area')]);
});
