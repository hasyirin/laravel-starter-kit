<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Models\Role;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\Shared\Permission\Seeders\RoleSeeder;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use App\Livewire\Pages\Admin\Controls\Role\Show;
use App\Livewire\Pages\Admin\Controls\Role\Tabs;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->givePermissionTo('admin.access', 'admin.controls.roles.view', 'admin.controls.roles.update', 'admin.controls.roles.update-permissions');
    $this->actingAs($this->admin);
    $this->role = Role::query()->create(['name' => 'editor']);
    $this->role->givePermissionTo('admin.access');
});

test('viewing a role needs view', function (): void {
    $this->admin->revokePermissionTo('admin.controls.roles.view');

    $this->get(route('admin.controls.roles.show', $this->role))->assertForbidden();

    $this->admin->givePermissionTo('admin.controls.roles.view');

    $this->get(route('admin.controls.roles.show', $this->role))
        ->assertOk()
        ->assertSeeLivewire(Show::class)
        ->assertSeeLivewire(Tabs::class)
        ->assertSee('<title>editor - '.config('app.name').'</title>', false);
});

test('the form shows the name and the permissions', function (): void {
    Livewire::test(Show::class, ['role' => $this->role])
        ->assertSet('form.name', 'editor')
        ->assertSet('form.permissions', ['admin.access']);
});

test('the browser cannot change the role of the form', function (): void {
    Livewire::test(Show::class, ['role' => $this->role])->set('form.role', Role::query()->create(['name' => 'other']));
})->throws(CannotUpdateLockedPropertyException::class);

test('the browser cannot change the role of the page', function (): void {
    Livewire::test(Show::class, ['role' => $this->role])->set('role', Role::query()->create(['name' => 'other'])->id);
})->throws(CannotUpdateLockedPropertyException::class);

test('saving renames the role and changes its permissions', function (): void {
    Livewire::test(Show::class, ['role' => $this->role])
        ->set('form.name', 'author')
        ->set('form.permissions', ['internal.access'])
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast-show', fn (string $name, array $params): bool => $params['slots']['text'] === 'Role updated.');

    $role = $this->role->fresh();
    expect($role->name)->toBe('author')
        ->and($role->getPermissionNames()->all())->toBe(['internal.access']);
});

test('the form is read-only without update', function (): void {
    $this->admin->revokePermissionTo('admin.controls.roles.update');

    Livewire::test(Show::class, ['role' => $this->role])
        ->assertDontSeeHtml('type="submit"')
        ->set('form.name', 'author')
        ->call('save')
        ->assertForbidden();

    expect($this->role->fresh()->name)->toBe('editor');
});

test('the permissions are read-only without update-permissions', function (): void {
    $this->admin->revokePermissionTo('admin.controls.roles.update-permissions');

    $component = Livewire::test(Show::class, ['role' => $this->role]);

    expect($component->instance()->editable('permissions'))->toBeFalse();

    $component->set('form.name', 'author')->set('form.permissions', [])->call('save')->assertHasNoErrors();

    expect($this->role->fresh()->getPermissionNames()->all())->toBe(['admin.access']);
});

test('the name of a system role is read-only, also with bypass', function (): void {
    $this->seed(RoleSeeder::class);
    $this->admin->givePermissionTo('bypass-authorization');
    $role = Role::findByName('super-admin');

    $component = Livewire::test(Show::class, ['role' => $role]);

    expect($component->instance()->editable('name'))->toBeFalse();

    $component->set('form.name', 'owner')->call('save')->assertHasNoErrors();

    expect($role->fresh()->name)->toBe('super-admin');
});

test('the name must stay unique', function (): void {
    Role::query()->create(['name' => 'author']);

    Livewire::test(Show::class, ['role' => $this->role])
        ->set('form.name', 'author')
        ->call('save')
        ->assertHasErrors(['form.name' => 'unique'])
        ->set('form.name', 'editor')
        ->call('save')
        ->assertHasNoErrors();
});

test('the form is read-only on a role with bypass-authorization without bypass', function (): void {
    $this->role->givePermissionTo('bypass-authorization');

    Livewire::test(Show::class, ['role' => $this->role])
        ->assertDontSeeHtml('type="submit"')
        ->assertDontSee('bypass-authorization')
        ->set('form.name', 'author')
        ->call('save')
        ->assertForbidden();

    expect($this->role->fresh()->name)->toBe('editor');
});

test('bypass-authorization is locked on a role that you hold', function (): void {
    $this->admin->givePermissionTo('bypass-authorization');
    $this->role->givePermissionTo('bypass-authorization');

    expect(Livewire::test(Show::class, ['role' => $this->role])->instance()->lockedPermissions->all())->toBe([]);

    Profile::factory()->for($this->admin)->withRoles('editor')->create();
    $this->admin->unsetRelation('profile');

    expect(Livewire::test(Show::class, ['role' => $this->role])->instance()->lockedPermissions->all())->toBe(['bypass-authorization']);
});

test('the breadcrumbs lead back to the roles', function (): void {
    $this->get(route('admin.controls.roles.show', $this->role))
        ->assertSee(route('admin.controls.roles.index'))
        ->assertSeeInOrder([__('permission/roles.title'), 'editor']);
});
