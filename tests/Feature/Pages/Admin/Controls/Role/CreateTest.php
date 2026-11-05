<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Models\Role;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Pages\Admin\Controls\Role\Create;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'system.access');
    $this->actingAs($this->actor);
});

test('creating a role needs create', function (): void {
    $this->get(route('admin.controls.roles.create'))->assertForbidden();

    grantPermissions($this->actor, 'admin.controls.roles.create');

    $this->get(route('admin.controls.roles.create'))->assertOk()->assertSeeLivewire(Create::class);
});

test('a new role opens on its page with the ticked permissions', function (): void {
    grantPermissions($this->actor, 'admin.controls.roles.create', 'admin.controls.roles.update-permissions');

    Livewire::test(Create::class)
        ->set('form.name', 'editor')
        ->set('form.permissions', ['system.access'])
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast-show', fn (string $name, array $params): bool => $params['slots']['text'] === 'Role created.')
        ->assertRedirect(route('admin.controls.roles.show', Role::findByName('editor')));

    expect(Role::findByName('editor')->getPermissionNames()->all())->toBe(['system.access']);
});

test('the permissions are left out without update-permissions', function (): void {
    grantPermissions($this->actor, 'admin.controls.roles.create');

    Livewire::test(Create::class)
        ->set('form.name', 'editor')
        ->set('form.permissions', ['system.access'])
        ->call('save')
        ->assertHasNoErrors();

    expect(Role::findByName('editor')->permissions)->toBeEmpty();
});

test('the name is required and unique', function (): void {
    grantPermissions($this->actor, 'admin.controls.roles.create');
    Role::query()->create(['name' => 'editor']);

    Livewire::test(Create::class)
        ->call('save')
        ->assertHasErrors(['form.name' => 'required'])
        ->set('form.name', 'editor')
        ->call('save')
        ->assertHasErrors(['form.name' => 'unique']);
});

test('bypass-authorization is refused without bypass', function (): void {
    grantPermissions($this->actor, 'admin.controls.roles.create', 'admin.controls.roles.update-permissions');

    Livewire::test(Create::class)
        ->set('form.name', 'owner')
        ->set('form.permissions', ['bypass-authorization'])
        ->call('save')
        ->assertHasErrors(['form.permissions.0']);

    expect(Role::query()->where('name', 'owner')->exists())->toBeFalse();
});
