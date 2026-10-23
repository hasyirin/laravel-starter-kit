<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Models\Permission;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;

test('a user has the permissions of their active profile', function (): void {
    $user = User::factory()->create();
    Profile::factory()->for($user)->withPermissions(Permission::query()->create(['name' => 'admin.access']))->create();

    expect($user->can('admin.access'))->toBeTrue();
});

test('a user does not have the permissions of an inactive profile', function (): void {
    $user = User::factory()->create();
    Profile::factory()->for($user)->withPermissions(Permission::query()->create(['name' => 'admin.access']))->create();
    Profile::factory()->for($user)->create();

    expect($user->can('admin.access'))->toBeFalse();
});
