<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;

test('guests are redirected to the login page', function (): void {
    $response = $this->get(route('internal.dashboard'));

    $response->assertRedirect(route('login'));
});

test('users without the internal access permission are forbidden', function (): void {
    $this->seed(PermissionSeeder::class);
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('internal.dashboard'));

    $response->assertForbidden();
});

test('users with the internal access permission can visit the internal dashboard', function (): void {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->create();
    $user->givePermissionTo('internal.access');
    $this->actingAs($user);

    $response = $this->get(route('internal.dashboard'));

    $response->assertOk();
});
