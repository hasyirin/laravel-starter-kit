<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Actions\DeleteSubdistrict;
use App\Domain\Shared\Geography\Models\Address;
use App\Domain\Shared\Geography\Models\Subdistrict;
use App\Domain\Shared\Organization\Models\Organization;

test('deleting a subdistrict that nothing uses soft-deletes only that subdistrict', function (): void {
    $subdistrict = Subdistrict::factory()->create();

    DeleteSubdistrict::make()->execute($subdistrict);

    expect($subdistrict->fresh()->trashed())->toBeTrue()
        ->and(Subdistrict::withTrashed()->count())->toBe(1);
});

test('a subdistrict that an address uses cannot be deleted', function (): void {
    $subdistrict = Subdistrict::factory()->create();
    Address::factory()->for(Organization::factory(), 'addressable')->create(['subdistrict_id' => $subdistrict->id]);

    expect(fn () => DeleteSubdistrict::make()->execute($subdistrict))->toThrow(LogicException::class, 'A subdistrict that has addresses cannot be deleted.')
        ->and($subdistrict->fresh()->trashed())->toBeFalse();
});
