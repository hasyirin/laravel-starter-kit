<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Models\Role;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\Shared\Permission\Seeders\RoleSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Pages\Internal\Controls\Role\Show;
use App\Livewire\Pages\Internal\Controls\Role\Tabs;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'internal.access', 'internal.controls.roles.view', 'internal.controls.roles.update', 'internal.controls.roles.update-permissions');
    $this->actingAs($this->actor);
    $this->role = Role::query()->create(['name' => 'editor']);
    $this->role->givePermissionTo('system.access');
});

test('viewing a role needs view', function (): void {
    revokePermissions($this->actor, 'internal.controls.roles.view');

    $this->get(route('internal.controls.roles.show', $this->role))->assertForbidden();

    grantPermissions($this->actor, 'internal.controls.roles.view');

    $this->get(route('internal.controls.roles.show', $this->role))
        ->assertOk()
        ->assertSeeLivewire(Show::class)
        ->assertSeeLivewire(Tabs::class)
        ->assertSee('<title>editor - '.config('app.name').'</title>', false);
});

test('the form shows the name and the permissions', function (): void {
    Livewire::test(Show::class, ['role' => $this->role])
        ->assertSet('form.name', 'editor')
        ->assertSet('form.permissions', ['system.access']);
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
    revokePermissions($this->actor, 'internal.controls.roles.update');

    Livewire::test(Show::class, ['role' => $this->role])
        ->assertDontSeeHtml('type="submit"')
        ->set('form.name', 'author')
        ->call('save')
        ->assertForbidden();

    expect($this->role->fresh()->name)->toBe('editor');
});

test('the permissions are read-only without update-permissions', function (): void {
    revokePermissions($this->actor, 'internal.controls.roles.update-permissions');

    $component = Livewire::test(Show::class, ['role' => $this->role]);

    expect($component->instance()->editable('permissions'))->toBeFalse();

    $component->set('form.name', 'author')->set('form.permissions', [])->call('save')->assertHasNoErrors();

    expect($this->role->fresh()->getPermissionNames()->all())->toBe(['system.access']);
});

test('the name of a system role is read-only, also with bypass', function (): void {
    $this->seed(RoleSeeder::class);
    grantPermissions($this->actor, 'bypass-authorization');
    $role = Role::findByName('system-admin');

    $component = Livewire::test(Show::class, ['role' => $role]);

    expect($component->instance()->editable('name'))->toBeFalse();

    $component->set('form.name', 'owner')->call('save')->assertHasNoErrors();

    expect($role->fresh()->name)->toBe('system-admin');
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
    grantPermissions($this->actor, 'bypass-authorization');
    $this->role->givePermissionTo('bypass-authorization');

    expect(Livewire::test(Show::class, ['role' => $this->role])->instance()->lockedPermissions->all())->toBe([]);

    grantRoles($this->actor, $this->role);

    expect(Livewire::test(Show::class, ['role' => $this->role])->instance()->lockedPermissions->all())->toBe(['bypass-authorization']);
});

test('the breadcrumbs lead back to the roles', function (): void {
    $this->get(route('internal.controls.roles.show', $this->role))
        ->assertSee(route('internal.controls.roles.index'))
        ->assertSeeInOrder([__('permission/roles.title'), 'editor']);
});
