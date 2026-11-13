<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Actions\Impersonation\StartImpersonation;
use App\Domain\User\Models\User;
use App\Domain\User\Support\Impersonation;

test('a new login ends an impersonation left in the session', function (): void {
    $this->seed(PermissionSeeder::class);
    $impersonator = User::factory()->create();
    $user = User::factory()->create();
    $next = User::factory()->create();
    grantPermissions($impersonator, 'internal.controls.users.impersonate', 'internal.controls.users.update');

    $this->actingAs($impersonator);
    StartImpersonation::make()->execute($impersonator, $user, route('internal.controls.users.index'));
    $user->delete();
    auth()->forgetGuards();

    $this->get(route('dashboard'))->assertRedirect(route('login'));
    auth()->forgetGuards();
    $this->post(route('login.store'), ['email' => $next->email, 'password' => 'password']);

    $this->assertAuthenticatedAs($next);
    expect(Impersonation::active())->toBeFalse();

    auth()->forgetGuards();
    $this->post(route('impersonation.stop'))->assertForbidden();
    $this->assertAuthenticatedAs($next);
});
