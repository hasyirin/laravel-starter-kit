<?php

declare(strict_types=1);

use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Position;
use App\Domain\Shared\Permission\Models\Permission;
use App\Domain\Shared\Permission\Models\Role;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\Shared\Permission\Seeders\RoleSeeder;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use App\Livewire\Components\Option;
use App\Livewire\Pages\Internal\Controls\User\ImpersonateModal;
use App\Livewire\Pages\Internal\Controls\User\Show;
use App\Livewire\Pages\Internal\Controls\User\Tabs;
use Carbon\CarbonImmutable;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, ['internal.access', 'internal.controls.users.view', 'internal.controls.users.update']);
    $this->actingAs($this->actor);
    $this->subject = User::factory()->create(['email_verified_at' => null]);
});

/**
 * @return array<string, bool>
 */
function roleOptionStates(Testable $component): array
{
    return collect($component->instance()->roleOptions)
        ->mapWithKeys(fn (Option $option): array => [$option->value() => $option->isDisabled()])
        ->all();
}

/**
 * @param  array<string, mixed>  $node
 * @return array<int, string>
 */
function treeNames(array $node): array
{
    $childNames = collect($node['children'])->flatMap(fn (array $child): array => treeNames($child));

    return collect($node['permissions'])
        ->pluck('name')
        ->merge($childNames)
        ->all();
}

test('viewing a user needs view', function (): void {
    revokePermissions($this->actor, 'internal.controls.users.view');

    $this->get(route('internal.controls.users.show', $this->subject))->assertForbidden();

    grantPermissions($this->actor, 'internal.controls.users.view');

    $this->get(route('internal.controls.users.show', $this->subject))
        ->assertOk()
        ->assertSeeLivewire(Show::class)
        ->assertSee($this->subject->name);
});

test('the form shows the current placement', function (): void {
    $department = Department::factory()->create();
    Profile::factory()->for($this->subject)->create([
        'organization_id' => $department->organization_id,
        'department_id' => $department->id,
    ]);

    Livewire::test(Show::class, ['user' => $this->subject])
        ->assertSet('form.name', $this->subject->name)
        ->assertSet('form.email', $this->subject->email)
        ->assertSet('form.organization_id', $department->organization_id)
        ->assertSet('form.department_id', $department->id)
        ->assertSet('form.branch_id', null);
});

test('the form ticks the roles and the direct permissions of the current profile', function (): void {
    Role::query()->create(['name' => 'editor', 'guard_name' => 'web']);
    Role::query()->create(['name' => 'viewer', 'guard_name' => 'web']);
    Profile::factory()->for($this->subject)->withRoles('editor')->withPermissions('system.access')->create();
    Profile::factory()->for($this->subject)->withRoles('viewer')->withPermissions('internal.access')->create();

    Livewire::test(Show::class, ['user' => $this->subject])
        ->assertSet('form.roles', ['viewer'])
        ->assertSet('form.permissions', ['internal.access']);
});

test('the heading names the position, department, and organization of the user', function (): void {
    $department = Department::factory()->create(['name' => 'Finance']);
    $department->organization->update(['name' => 'Head Office']);

    $position = Position::factory()->forDepartment($department)->create(['name' => 'Clerk']);
    Profile::factory()->for($this->subject)->create([
        'organization_id' => $department->organization_id,
        'department_id' => $department->id,
        'position_id' => $position->id,
    ]);

    expect(Livewire::test(Show::class, ['user' => $this->subject])->instance()->subheading)->toBe('Clerk · Finance · Head Office');
});

test('the heading leaves out the parts of the placement that the user does not have', function (): void {
    $department = Department::factory()->create(['name' => 'Finance']);
    $department->organization->update(['name' => 'Head Office']);
    Profile::factory()->for($this->subject)->create([
        'organization_id' => $department->organization_id,
        'department_id' => $department->id,
    ]);

    expect(Livewire::test(Show::class, ['user' => $this->subject])->instance()->subheading)->toBe('Finance · Head Office');
});

test('the heading shows the email of a user without a placement', function (): void {
    expect(Livewire::test(Show::class, ['user' => $this->subject])->instance()->subheading)->toBe($this->subject->email);
});

test('the details show when the user was created and verified the email', function (): void {
    context()->addHidden(['timezone' => 'Asia/Kuala_Lumpur', 'locale' => 'en-GB']);
    $user = User::factory()->create([
        'created_at' => CarbonImmutable::parse('2026-01-02 08:30', 'UTC'),
        'email_verified_at' => CarbonImmutable::parse('2026-01-03 09:45', 'UTC'),
    ]);

    Livewire::test(Show::class, ['user' => $user])
        ->assertSeeInOrder([__('user/users.created_at'), '2 Jan 2026, 16:30', __('user/users.email_verified_at'), '3 Jan 2026, 17:45']);
});

test('the browser cannot change the user of the form', function (): void {
    Livewire::test(Show::class, ['user' => $this->subject])
        ->set('form.user', $this->actor->id);
})->throws(CannotUpdateLockedPropertyException::class);

test('the browser cannot change the user of the page', function (): void {
    Livewire::test(Show::class, ['user' => $this->subject])
        ->set('user', $this->actor->id);
})->throws(CannotUpdateLockedPropertyException::class);

test('saving keeps the roles of the profile', function (): void {
    $role = Role::query()->create(['name' => 'editor', 'guard_name' => 'web']);
    Profile::factory()->for($this->subject)->withRoles($role)->create();
    $department = Department::factory()->create();

    Livewire::test(Show::class, ['user' => $this->subject])
        ->set('form.name', 'Renamed')
        ->set('form.organization_id', $department->organization_id)
        ->set('form.department_id', $department->id)
        ->call('save')
        ->assertHasNoErrors();

    $profile = $this->subject->fresh()->profile;
    expect($this->subject->fresh()->name)->toBe('Renamed')
        ->and($profile->department_id)->toBe($department->id)
        ->and($profile->getRoleNames()->all())->toBe(['editor']);
});

test('saving puts the placement, the roles, and the permissions in one new profile', function (): void {
    grantPermissions($this->actor, 'internal.controls.users.update-roles', 'internal.controls.users.update-permissions');
    Role::query()->create(['name' => 'editor', 'guard_name' => 'web']);
    Profile::factory()->for($this->subject)->create();
    $department = Department::factory()->create();

    Livewire::test(Show::class, ['user' => $this->subject])
        ->set('form.organization_id', $department->organization_id)
        ->set('form.department_id', $department->id)
        ->set('form.roles', ['editor'])
        ->set('form.permissions', ['internal.access'])
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast-show', fn (string $name, array $params): bool => $params['slots']['text'] === 'User updated.');

    $profile = $this->subject->fresh()->profile;
    expect($this->subject->profiles()->count())->toBe(2)
        ->and($profile->department_id)->toBe($department->id)
        ->and($profile->getRoleNames()->all())->toBe(['editor'])
        ->and($profile->getPermissionNames()->all())->toBe(['internal.access']);
});

test('a blank password keeps the old one', function (): void {
    $hash = $this->subject->password;

    Livewire::test(Show::class, ['user' => $this->subject])
        ->set('form.name', 'Renamed')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->subject->fresh()->password)->toBe($hash);
});

test('the form is read-only without update', function (): void {
    revokePermissions($this->actor, 'internal.controls.users.update');

    Livewire::test(Show::class, ['user' => $this->subject])
        ->assertDontSeeHtml('type="submit"')
        ->assertSeeHtml('wire:model="form.name"')
        ->set('form.name', 'Renamed')
        ->call('save')
        ->assertForbidden();

    expect($this->subject->fresh()->name)->not->toBe('Renamed');
});

test('the form is read-only on a user with bypass-authorization without bypass', function (): void {
    grantPermissions($this->subject, 'bypass-authorization');

    Livewire::test(Show::class, ['user' => $this->subject])
        ->assertDontSeeHtml('type="submit"')
        ->set('form.name', 'Renamed')
        ->call('save')
        ->assertForbidden();

    expect($this->subject->fresh()->name)->not->toBe('Renamed');
});

test('the form is editable with update', function (): void {
    Livewire::test(Show::class, ['user' => $this->subject])
        ->assertSeeHtmlInOrder(['type="submit"', __('common/buttons.update')]);
});

test('the roles are read-only without update-roles', function (): void {
    Role::query()->create(['name' => 'editor', 'guard_name' => 'web']);
    Profile::factory()->for($this->subject)->withRoles('editor')->create();

    $component = Livewire::test(Show::class, ['user' => $this->subject]);

    expect(roleOptionStates($component))->toBe(['editor' => true]);

    $component->set('form.roles', [])->call('save')->assertHasNoErrors();

    expect($this->subject->fresh()->profile->getRoleNames()->all())->toBe(['editor']);
});

test('the permissions are read-only without update-permissions', function (): void {
    $component = Livewire::test(Show::class, ['user' => $this->subject]);

    expect($component->instance()->editable('permissions'))->toBeFalse();

    $component->set('form.name', 'Renamed')->set('form.permissions', ['internal.access'])->call('save')->assertHasNoErrors();

    $subject = $this->subject->fresh();
    expect($subject->name)->toBe('Renamed')
        ->and($subject->profile->getPermissionNames()->all())->toBe([]);
});

test('your own roles and permissions are read-only without bypass', function (): void {
    grantPermissions($this->actor, 'internal.controls.users.update-roles', 'internal.controls.users.update-permissions');
    Role::query()->create(['name' => 'editor', 'guard_name' => 'web']);
    grantRoles($this->actor, 'editor');

    $component = Livewire::test(Show::class, ['user' => $this->actor]);

    expect(roleOptionStates($component))->toBe(['editor' => true])
        ->and($component->instance()->editable('permissions'))->toBeFalse();
});

test('your own bypass role is locked', function (): void {
    $this->seed(RoleSeeder::class);
    grantPermissions($this->actor, 'bypass-authorization');
    Role::query()->create(['name' => 'editor', 'guard_name' => 'web']);
    grantRoles($this->actor, 'system-admin', 'editor');

    expect(roleOptionStates(Livewire::test(Show::class, ['user' => $this->actor])))->toBe(['editor' => false, 'system-admin' => true]);
});

test('the bypass role and permission of another user stay editable with bypass', function (): void {
    $this->seed(RoleSeeder::class);
    grantPermissions($this->actor, 'bypass-authorization');
    Profile::factory()->for($this->subject)->withRoles('system-admin')->withPermissions('bypass-authorization')->create();

    $component = Livewire::test(Show::class, ['user' => $this->subject]);

    expect(roleOptionStates($component))->toBe(['system-admin' => false])
        ->and($component->instance()->lockedPermissions->all())->toBe([]);
});

test('your own direct bypass-authorization is locked', function (): void {
    grantPermissions($this->actor, 'bypass-authorization');

    $component = Livewire::test(Show::class, ['user' => $this->actor]);

    expect($component->instance()->lockedPermissions->all())->toBe(['bypass-authorization'])
        ->and($component->instance()->editable('permissions'))->toBeTrue();
});

test('a permission that a held role gives can be saved as direct', function (): void {
    grantPermissions($this->actor, 'internal.controls.users.update-permissions');
    Role::query()->create(['name' => 'editor', 'guard_name' => 'web'])->givePermissionTo('system.access');
    Profile::factory()->for($this->subject)->withRoles('editor')->create();

    Livewire::test(Show::class, ['user' => $this->subject])
        ->set('form.permissions', ['system.access', 'system.horizon.view'])
        ->call('save')
        ->assertHasNoErrors();

    expect($this->subject->fresh()->profile->getPermissionNames()->sort()->values()->all())->toBe(['system.access', 'system.horizon.view']);
});

test('a permission that a newly ticked role gives can be saved as direct', function (): void {
    grantPermissions($this->actor, 'internal.controls.users.update-roles', 'internal.controls.users.update-permissions');
    Role::query()->create(['name' => 'editor', 'guard_name' => 'web'])->givePermissionTo('system.access');

    Livewire::test(Show::class, ['user' => $this->subject])
        ->set('form.roles', ['editor'])
        ->set('form.permissions', ['system.access', 'system.horizon.view'])
        ->call('save')
        ->assertHasNoErrors();

    $profile = $this->subject->fresh()->profile;
    expect($profile->getRoleNames()->all())->toBe(['editor'])
        ->and($profile->getPermissionNames()->sort()->values()->all())->toBe(['system.access', 'system.horizon.view']);
});

test('a direct permission that a role also gives stays direct', function (): void {
    grantPermissions($this->actor, 'internal.controls.users.update-permissions');
    Role::query()->create(['name' => 'editor', 'guard_name' => 'web'])->givePermissionTo('system.access');
    Profile::factory()->for($this->subject)->withRoles('editor')->withPermissions('system.access')->create();

    Livewire::test(Show::class, ['user' => $this->subject])
        ->set('form.permissions', ['system.access', 'system.horizon.view'])
        ->call('save')
        ->assertHasNoErrors();

    expect($this->subject->fresh()->profile->getPermissionNames()->sort()->values()->all())->toBe(['system.access', 'system.horizon.view']);
});

test('a role that does not exist is refused', function (): void {
    grantPermissions($this->actor, 'internal.controls.users.update-roles');

    Livewire::test(Show::class, ['user' => $this->subject])
        ->set('form.roles', ['ghost'])
        ->call('save')
        ->assertHasErrors('form.roles.0');

    expect($this->subject->fresh()->profile)->toBeNull();
});

test('a permission outside the config is refused', function (): void {
    grantPermissions($this->actor, 'internal.controls.users.update-permissions');
    Permission::query()->create(['name' => 'ghost.view', 'guard_name' => 'web']);

    Livewire::test(Show::class, ['user' => $this->subject])
        ->set('form.permissions', ['ghost.view'])
        ->call('save')
        ->assertHasErrors('form.permissions.0');

    expect($this->subject->fresh()->profile)->toBeNull();
});

test('roles that carry bypass are not offered without bypass', function (): void {
    $this->seed(RoleSeeder::class);
    grantPermissions($this->actor, 'internal.controls.users.update-roles');
    Role::query()->create(['name' => 'root', 'guard_name' => 'web'])->givePermissionTo('bypass-authorization');
    Role::query()->create(['name' => 'editor', 'guard_name' => 'web']);

    expect(roleOptionStates(Livewire::test(Show::class, ['user' => $this->subject])))->toBe(['editor' => false]);
});

test('the system-admin role without bypass is offered like any other role', function (): void {
    grantPermissions($this->actor, 'internal.controls.users.update-roles');
    Role::query()->create(['name' => 'system-admin', 'guard_name' => 'web']);

    expect(roleOptionStates(Livewire::test(Show::class, ['user' => $this->subject])))->toBe(['system-admin' => false]);
});

test('a bypass role that the user holds still shows without bypass', function (): void {
    $this->seed(RoleSeeder::class);
    Profile::factory()->for($this->subject)->withRoles('system-admin')->create();

    expect(roleOptionStates(Livewire::test(Show::class, ['user' => $this->subject])))->toBe(['system-admin' => true]);
});

test('ticking a role that carries bypass is refused without bypass', function (string $role): void {
    $this->seed(RoleSeeder::class);
    grantPermissions($this->actor, 'internal.controls.users.update-roles');
    Role::query()->create(['name' => 'root', 'guard_name' => 'web'])->givePermissionTo('bypass-authorization');

    Livewire::test(Show::class, ['user' => $this->subject])
        ->set('form.roles', [$role])
        ->call('save')
        ->assertForbidden();

    expect($this->subject->fresh()->profile)->toBeNull();
})->with(['system-admin', 'root']);

test('the system-admin role is offered with bypass', function (): void {
    $this->seed(RoleSeeder::class);
    grantPermissions($this->actor, 'bypass-authorization');

    expect(roleOptionStates(Livewire::test(Show::class, ['user' => $this->subject])))->toBe(['system-admin' => false]);
});

test('the roles card says so when there is no role to tick', function (): void {
    Livewire::test(Show::class, ['user' => $this->subject])
        ->assertSeeInOrder([__('user/users.roles'), __('common/records.none'), __('user/users.permissions.heading')]);
});

test('bypass-authorization is hidden without bypass', function (): void {
    expect(treeNames(Livewire::test(Show::class, ['user' => $this->subject])->instance()->permissionTree))->not->toContain('bypass-authorization');

    grantPermissions($this->actor, 'bypass-authorization');

    expect(treeNames(Livewire::test(Show::class, ['user' => $this->subject])->instance()->permissionTree))->toContain('bypass-authorization');
});

test('bypass-authorization is not sent to the browser without bypass', function (): void {
    $this->seed(RoleSeeder::class);
    Profile::factory()->for($this->subject)->withRoles('system-admin')->withPermissions('bypass-authorization', 'internal.access')->create();

    Livewire::test(Show::class, ['user' => $this->subject])
        ->assertSet('form.permissions', ['internal.access'])
        ->assertDontSee('bypass-authorization');
});

test('an unverified email can be marked as verified', function (): void {
    Livewire::test(Show::class, ['user' => $this->subject])
        ->assertSee(__('user/users.unverified'))
        ->call('verifyEmail')
        ->assertDontSee(__('user/users.unverified'));

    expect($this->subject->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('marking an email as verified needs update', function (): void {
    revokePermissions($this->actor, 'internal.controls.users.update');

    Livewire::test(Show::class, ['user' => $this->subject])
        ->call('verifyEmail')
        ->assertForbidden();

    expect($this->subject->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('the verify button is hidden once verified', function (): void {
    $this->subject->markEmailAsVerified();

    Livewire::test(Show::class, ['user' => $this->subject])
        ->assertDontSee(__('user/users.unverified'))
        ->assertDontSee(__('user/users.mark_as_verified'));
});

test('a user without a profile opens', function (): void {
    expect($this->subject->profile)->toBeNull();

    Livewire::test(Show::class, ['user' => $this->subject])
        ->assertOk()
        ->assertSet('form.organization_id', null);
});

test('the show page opens without a tab bar when no tab is available', function (): void {
    config()->set('fortify.features', []);

    $this->get(route('internal.controls.users.show', $this->subject))
        ->assertOk()
        ->assertSeeLivewire(Tabs::class)
        ->assertDontSeeHtml('data-tab=');
});

test('a user opened from a role leads back to the role', function (): void {
    grantPermissions($this->actor, 'internal.controls.roles.view');
    $role = Role::query()->create(['name' => 'editor']);

    $this->get(route('internal.controls.roles.users.show', [$role, $this->subject]))
        ->assertOk()
        ->assertSeeInOrder([route('internal.controls.roles.index'), route('internal.controls.roles.show', $role), route('internal.controls.roles.show', ['role' => $role, 'tab' => 'users']), $this->subject->email])
        ->assertDontSee(route('internal.controls.users.index'));
});

test('the back button of a user opened from a role leads to the users tab of the role', function (): void {
    grantPermissions($this->actor, 'internal.controls.roles.view');
    $role = Role::query()->create(['name' => 'editor']);
    $tab = route('internal.controls.roles.show', ['role' => $role, 'tab' => 'users']);

    $html = $this->get(route('internal.controls.roles.users.show', [$role, $this->subject]))->assertOk()->getContent();

    expect(substr_count($html, 'href="'.$tab.'"'))->toBe(2);
});

test('a user opened from a role needs view on the role', function (): void {
    $role = Role::query()->create(['name' => 'editor']);

    $this->get(route('internal.controls.roles.users.show', [$role, $this->subject]))->assertForbidden();
});

test('the impersonate button shows only when the policy allows it', function (): void {
    $this->get(route('internal.controls.users.show', $this->subject))
        ->assertDontSee('data-test="impersonate-button"', false);

    grantPermissions($this->actor, 'internal.controls.users.impersonate');

    $this->get(route('internal.controls.users.show', $this->subject))
        ->assertSee('data-test="impersonate-button"', false)
        ->assertSeeLivewire(ImpersonateModal::class);
});

test('changing the phone number on the form unverifies it', function (): void {
    Livewire::test(Show::class, ['user' => $this->subject])
        ->set('form.phone', '+60198765432')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->subject->fresh()->phone_verified_at)->toBeNull();
});

test('removing bypass from a user without a phone number asks for one', function (): void {
    $this->seed(RoleSeeder::class);
    grantPermissions($this->actor, 'bypass-authorization');
    $subject = User::factory()->withoutPhone()->create();
    grantRoles($subject, 'system-admin');

    Livewire::test(Show::class, ['user' => $subject])
        ->set('form.roles', [])
        ->call('save')
        ->assertHasErrors(['form.phone' => 'required']);
});

test('an unverified phone number can be marked as verified', function (): void {
    $subject = User::factory()->unverifiedPhone()->create();

    Livewire::test(Show::class, ['user' => $subject])
        ->call('verifyPhone')
        ->assertDispatched('modal-close', name: 'verify-phone');

    expect($subject->fresh()->hasVerifiedPhone())->toBeTrue();
});

test('marking a phone number as verified needs update', function (): void {
    revokePermissions($this->actor, 'internal.controls.users.update');
    $subject = User::factory()->unverifiedPhone()->create();

    Livewire::test(Show::class, ['user' => $subject])->call('verifyPhone')->assertForbidden();

    expect($subject->fresh()->hasVerifiedPhone())->toBeFalse();
});

test('the details show when the user verified the phone number', function (): void {
    context()->addHidden(['timezone' => 'Asia/Kuala_Lumpur', 'locale' => 'en-GB']);
    $subject = User::factory()->create(['phone_verified_at' => CarbonImmutable::parse('2026-01-03 09:45', 'UTC')]);

    Livewire::test(Show::class, ['user' => $subject])
        ->assertSeeInOrder([__('user/users.phone_verified_at'), '3 Jan 2026, 17:45'])
        ->assertDontSee(__('user/users.mark_as_verified'));
});

test('the details say so when the user has no phone number', function (): void {
    Livewire::test(Show::class, ['user' => User::factory()->withoutPhone()->create()])
        ->assertSee(__('user/users.no_phone'));
});
