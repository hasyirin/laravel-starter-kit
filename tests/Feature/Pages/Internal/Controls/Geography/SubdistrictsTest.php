<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Models\District;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\Internal\Geography\SubdistrictDataTable;
use App\Livewire\Pages\Internal\Controls\Geography\SubdistrictModal;
use App\Livewire\Pages\Internal\Controls\Geography\Subdistricts;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'internal.access');
    $this->actingAs($this->actor);
});

test('the subdistricts need geography view', function (?bool $withDistrict): void {
    $params = $withDistrict ? ['district' => District::factory()->create()] : [];

    Livewire::test(Subdistricts::class, $params)->assertForbidden();

    grantPermissions($this->actor, 'internal.controls.geography.view-any');

    Livewire::test(Subdistricts::class, $params)
        ->assertSeeLivewire(SubdistrictDataTable::class)
        ->assertSeeLivewire(SubdistrictModal::class);
})->with([
    'of a district' => [true],
    'of all districts' => [false],
]);
