<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Models\PostOffice;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\Internal\Geography\PostcodeDataTable;
use App\Livewire\Pages\Internal\Controls\Geography\PostcodeModal;
use App\Livewire\Pages\Internal\Controls\Geography\Postcodes;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'internal.access');
    $this->actingAs($this->actor);
});

test('the postcodes need geography view', function (bool $withPostOffice): void {
    $params = $withPostOffice ? ['postOffice' => PostOffice::factory()->create()] : [];

    Livewire::test(Postcodes::class, $params)->assertForbidden();

    grantPermissions($this->actor, 'internal.controls.geography.view-any');

    Livewire::test(Postcodes::class, $params)
        ->assertSeeLivewire(PostcodeDataTable::class)
        ->assertSeeLivewire(PostcodeModal::class);
})->with([
    'of a city' => [true],
    'of all cities' => [false],
]);
