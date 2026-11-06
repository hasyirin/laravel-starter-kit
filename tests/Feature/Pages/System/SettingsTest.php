<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;

test('users with the system access permission are sent to their user information settings', function (): void {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->create();
    grantPermissions($user, 'system.access');
    $this->actingAs($user);

    $response = $this->get(route('system.settings'));

    $response->assertRedirect()->assertHeader('Location', route('user-information.edit', absolute: false));
});
