<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;

test('a holder of bypass-authorization passes with an unverified email', function (): void {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->unverified()->create();
    grantPermissions($user, 'bypass-authorization');
    $this->actingAs($user);

    $this->get(route('dashboard'))->assertOk();
});
