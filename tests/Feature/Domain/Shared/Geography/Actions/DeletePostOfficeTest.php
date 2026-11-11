<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Actions\DeletePostOffice;
use App\Domain\Shared\Geography\Models\Address;
use App\Domain\Shared\Geography\Models\Postcode;
use App\Domain\Shared\Geography\Models\PostOffice;
use App\Domain\Shared\Organization\Models\Organization;

test('deleting a city that nothing uses soft-deletes only that city', function (): void {
    $postOffice = PostOffice::factory()->create();

    DeletePostOffice::make()->execute($postOffice);

    expect($postOffice->fresh()->trashed())->toBeTrue()
        ->and(PostOffice::withTrashed()->count())->toBe(1);
});

test('a city with a postcode cannot be deleted', function (): void {
    $postOffice = Postcode::factory()->create()->postOffice;

    expect(fn () => DeletePostOffice::make()->execute($postOffice))->toThrow(LogicException::class, 'A city that has postcodes or addresses cannot be deleted.')
        ->and($postOffice->fresh()->trashed())->toBeFalse();
});

test('a city that an address uses cannot be deleted', function (): void {
    $postOffice = PostOffice::factory()->create();
    Address::factory()->for(Organization::factory(), 'addressable')->create(['post_office_id' => $postOffice->id]);

    expect(fn () => DeletePostOffice::make()->execute($postOffice))->toThrow(LogicException::class)
        ->and($postOffice->fresh()->trashed())->toBeFalse();
});
