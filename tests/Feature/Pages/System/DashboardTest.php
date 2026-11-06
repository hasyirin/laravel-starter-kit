<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Pages\System\Dashboard;

test('guests are redirected to the login page', function (): void {
    $response = $this->get(route('system.dashboard'));

    $response->assertRedirect(route('login'));
});

test('users without the system access permission are forbidden', function (): void {
    $this->seed(PermissionSeeder::class);
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('system.dashboard'));

    $response->assertForbidden();
});

test('users with the system access permission can visit the system dashboard', function (): void {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->create();
    grantPermissions($user, 'system.access');
    $this->actingAs($user);

    $response = $this->get(route('system.dashboard'));

    $response->assertOk()
        ->assertSeeLivewire(Dashboard::class)
        ->assertSee('<title>'.__('navigation.dashboard').' - '.config('app.name').'</title>', false);
});
