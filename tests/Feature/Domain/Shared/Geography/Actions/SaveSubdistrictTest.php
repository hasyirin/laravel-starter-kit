<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Actions\SaveSubdistrict;
use App\Domain\Shared\Geography\Models\District;
use App\Domain\Shared\Geography\Models\Subdistrict;

test('saving creates and updates a subdistrict under its district, also its code', function (): void {
    $first = District::factory()->create();
    $second = District::factory()->create();

    $subdistrict = SaveSubdistrict::make()->execute($first, new Subdistrict, '01', 'Lenga');

    expect($subdistrict->exists)->toBeTrue()
        ->and($subdistrict->district_id)->toBe($first->id);

    SaveSubdistrict::make()->execute($second, $subdistrict, '02', 'Linau');

    expect($subdistrict->fresh()->only('district_id', 'code', 'name'))->toBe(['district_id' => $second->id, 'code' => '02', 'name' => 'Linau']);
});

test('creating a subdistrict with the code of a trashed subdistrict of the same district restores that subdistrict', function (): void {
    $district = District::factory()->create();
    $trashed = Subdistrict::factory()->for($district)->create(['code' => '01']);
    $trashed->delete();

    $subdistrict = SaveSubdistrict::make()->execute($district, new Subdistrict, '01', 'Bandar');

    expect($subdistrict->id)->toBe($trashed->id)
        ->and($subdistrict->fresh()->trashed())->toBeFalse()
        ->and(Subdistrict::withTrashed()->count())->toBe(1);
});
