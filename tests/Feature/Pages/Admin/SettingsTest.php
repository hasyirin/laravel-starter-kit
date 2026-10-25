<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;

test('users with the admin access permission are sent to their profile settings', function (): void {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->create();
    $user->givePermissionTo('admin.access');
    $this->actingAs($user);

    $response = $this->get(route('admin.settings'));

    $response->assertRedirect()->assertHeader('Location', route('profile.edit', absolute: false));
});
