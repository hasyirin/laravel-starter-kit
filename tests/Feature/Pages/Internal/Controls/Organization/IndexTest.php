<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Pages\Internal\Controls\Organization\Index;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'internal.access');
    $this->actingAs($this->actor);
});

test('the organization list needs view-any', function (): void {
    $this->get(route('internal.controls.organizations.index'))->assertForbidden();

    grantPermissions($this->actor, 'internal.controls.organizations.view-any');

    $this->get(route('internal.controls.organizations.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class)
        ->assertSee('<title>'.__('organization/organizations.title').' - '.config('app.name').'</title>', false);
});

test('the navigation shows the organizations item only with view-any', function (): void {
    $this->get(route('internal.dashboard'))->assertDontSee(route('internal.controls.organizations.index'));

    grantPermissions($this->actor, 'internal.controls.organizations.view-any');

    $this->get(route('internal.dashboard'))->assertSee(route('internal.controls.organizations.index'));
});
