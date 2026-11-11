<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Actions\SavePostcode;
use App\Domain\Shared\Geography\Models\Postcode;
use App\Domain\Shared\Geography\Models\PostOffice;

test('saving creates and updates a postcode of a city with the code upper-cased', function (): void {
    $postOffice = PostOffice::factory()->create();

    $postcode = SavePostcode::make()->execute(postOffice: $postOffice, postcode: new Postcode, code: ' 84000 ', latitude: 2.04, longitude: 102.57);

    expect($postcode->post_office_id)->toBe($postOffice->id);

    SavePostcode::make()->execute(postOffice: $postOffice, postcode: $postcode, code: 'ab 1', latitude: null, longitude: null);

    expect($postcode->fresh()->only('code', 'latitude', 'longitude'))->toBe(['code' => 'AB 1', 'latitude' => null, 'longitude' => null]);
});

test('creating a postcode with the normalized code of a trashed postcode of the same city restores that postcode', function (): void {
    $postOffice = PostOffice::factory()->create();
    $trashed = Postcode::factory()->for($postOffice)->create(['code' => '84000']);
    $trashed->delete();

    $postcode = SavePostcode::make()->execute(postOffice: $postOffice, postcode: new Postcode, code: ' 84000', latitude: null, longitude: null);

    expect($postcode->id)->toBe($trashed->id)
        ->and($postcode->fresh()->trashed())->toBeFalse()
        ->and(Postcode::withTrashed()->count())->toBe(1);
});
