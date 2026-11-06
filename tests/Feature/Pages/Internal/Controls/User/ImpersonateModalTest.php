<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Models\Role;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Domain\User\Support\Impersonation;
use App\Http\Responses\LoginResponse;
use App\Livewire\Pages\Internal\Controls\User\ImpersonateModal;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);

    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'internal.access', 'internal.controls.users.view-any', 'internal.controls.users.view', 'internal.controls.users.update', 'internal.controls.users.impersonate');
    $this->actingAs($this->actor);

    $this->user = User::factory()->create();
});

test('the modal starts impersonation with the impersonator password', function (): void {
    Livewire::test(ImpersonateModal::class)
        ->call('show', $this->user->id)
        ->set('password', 'password')
        ->call('impersonate')
        ->assertHasNoErrors();

    expect(auth()->id())->toBe($this->user->id)
        ->and(Impersonation::impersonatorId())->toBe($this->actor->id);
});

test('a wrong password does not start impersonation', function (): void {
    Livewire::test(ImpersonateModal::class)
        ->call('show', $this->user->id)
        ->set('password', 'wrong-password')
        ->call('impersonate')
        ->assertHasErrors('password');

    expect(auth()->id())->toBe($this->actor->id);
});

test('the modal is forbidden without the impersonate permission', function (): void {
    revokePermissions($this->actor, 'internal.controls.users.impersonate');

    Livewire::test(ImpersonateModal::class)
        ->call('show', $this->user->id)
        ->assertForbidden();
});

test('starting from the users table stores the users index as the start url', function (): void {
    Livewire::test(ImpersonateModal::class, ['startsOnIndexPage' => true])
        ->call('show', $this->user->id)
        ->set('password', 'password')
        ->call('impersonate');

    expect(Impersonation::startUrl())->toBe(route('internal.controls.users.index'));
});

test('starting from the show page stores the show page as the start url', function (): void {
    Livewire::test(ImpersonateModal::class)
        ->call('show', $this->user->id)
        ->set('password', 'password')
        ->call('impersonate');

    expect(Impersonation::startUrl())->toBe(route('internal.controls.users.show', $this->user));
});

test('starting from the show page of a role stores that page as the start url', function (): void {
    $role = Role::query()->create(['name' => 'clerk', 'guard_name' => 'web']);
    grantPermissions($this->actor, 'internal.controls.roles.view');

    Livewire::test(ImpersonateModal::class, ['role' => $role])
        ->call('show', $this->user->id)
        ->set('password', 'password')
        ->call('impersonate');

    expect(Impersonation::startUrl())->toBe(route('internal.controls.roles.users.show', [$role, $this->user]));
});

test('the users index opens when the impersonated user can open it', function (): void {
    grantPermissions($this->user, 'internal.access', 'internal.controls.users.view-any');

    Livewire::test(ImpersonateModal::class, ['startsOnIndexPage' => true])
        ->call('show', $this->user->id)
        ->set('password', 'password')
        ->call('impersonate')
        ->assertRedirect(route('internal.controls.users.index'));
});

test('the show page opens when the impersonated user can open it', function (): void {
    grantPermissions($this->user, 'internal.access');

    Livewire::test(ImpersonateModal::class)
        ->call('show', $this->user->id)
        ->set('password', 'password')
        ->call('impersonate')
        ->assertRedirect(route('internal.controls.users.show', $this->user));
});

test('the show page of a role opens only when the impersonated user can view the role', function (): void {
    $role = Role::query()->create(['name' => 'clerk', 'guard_name' => 'web']);
    grantPermissions($this->actor, 'internal.controls.roles.view');
    grantPermissions($this->user, 'internal.access');

    Livewire::test(ImpersonateModal::class, ['role' => $role])
        ->call('show', $this->user->id)
        ->set('password', 'password')
        ->call('impersonate')
        ->assertRedirect(LoginResponse::home($this->user));
});

test('otherwise the impersonated user goes to their highest area', function (bool $startsOnIndexPage): void {
    Livewire::test(ImpersonateModal::class, ['startsOnIndexPage' => $startsOnIndexPage])
        ->call('show', $this->user->id)
        ->set('password', 'password')
        ->call('impersonate')
        ->assertRedirect(LoginResponse::home($this->user));
})->with(['from the users table' => true, 'from the show page' => false]);
