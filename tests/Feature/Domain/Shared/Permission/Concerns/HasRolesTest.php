<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Models\Role;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;

test('a user has the roles of their active profile', function (): void {
    $user = User::factory()->create();
    Profile::factory()->for($user)->withRoles(Role::query()->create(['name' => 'editor']))->create();

    expect($user->hasRole('editor'))->toBeTrue();
});

test('a user does not have the roles of an inactive profile', function (): void {
    $user = User::factory()->create();
    Profile::factory()->for($user)->withRoles(Role::query()->create(['name' => 'editor']))->create();
    Profile::factory()->for($user)->create();

    expect($user->hasRole('editor'))->toBeFalse();
});
