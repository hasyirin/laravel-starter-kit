<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Actions\Impersonation\StartImpersonation;
use App\Domain\User\Models\User;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);

    $this->impersonator = User::factory()->create();
    $this->user = User::factory()->create();
    grantPermissions($this->impersonator, 'internal.controls.users.impersonate', 'internal.controls.users.update');
});

function startImpersonation(User $impersonator, User $user): void
{
    test()->actingAs($impersonator);

    StartImpersonation::make()->execute($impersonator, $user, route('internal.controls.users.show', $user));
}

test('stopping redirects to the start url', function (): void {
    startImpersonation($this->impersonator, $this->user);

    $this->post(route('impersonation.stop'))->assertRedirect(route('internal.controls.users.show', $this->user));

    $this->assertAuthenticatedAs($this->impersonator);
});

test('an impersonation of a user with another password keeps the session signed in through the start and the stop', function (): void {
    $this->user->update(['password' => 'another-password']);
    $this->actingAs($this->impersonator)->get(route('dashboard'))->assertOk();
    startImpersonation($this->impersonator, $this->user);
    $this->get(route('dashboard'))->assertOk();
    $this->assertAuthenticatedAs($this->user);

    $this->post(route('impersonation.stop'));

    $this->get(route('dashboard'))->assertOk();
    $this->assertAuthenticatedAs($this->impersonator);
});

test('stopping redirects to the login page when the impersonator was deleted', function (): void {
    startImpersonation($this->impersonator, $this->user);
    $this->impersonator->delete();

    $this->post(route('impersonation.stop'))->assertRedirect(route('login'));

    $this->assertGuest();
});

test('stopping without an impersonation is forbidden', function (): void {
    $this->actingAs($this->user)->post(route('impersonation.stop'))->assertForbidden();

    $this->assertAuthenticatedAs($this->user);
});
