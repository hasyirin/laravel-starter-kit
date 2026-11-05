<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Pages\Internal\Controls\Role\Index;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'internal.access');
    $this->actingAs($this->actor);
});

test('the role list needs view-any', function (): void {
    $this->get(route('internal.controls.roles.index'))->assertForbidden();

    grantPermissions($this->actor, 'internal.controls.roles.view-any');

    $this->get(route('internal.controls.roles.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class)
        ->assertSee('<title>'.__('permission/roles.title').' - '.config('app.name').'</title>', false);
});

test('the navigation shows the roles item only with view-any', function (): void {
    $this->get(route('internal.dashboard'))->assertDontSee(route('internal.controls.roles.index'));

    grantPermissions($this->actor, 'internal.controls.roles.view-any');

    $this->get(route('internal.dashboard'))->assertSee(route('internal.controls.roles.index'));
});
