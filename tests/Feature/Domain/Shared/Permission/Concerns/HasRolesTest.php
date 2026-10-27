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

test('the role helpers and scopes count the roles of the active profile', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::query()->create(['name' => 'writer']));
    Profile::factory()->for($user)->withRoles(Role::query()->create(['name' => 'editor']))->create();
    $other = User::factory()->create();

    expect($user->hasAllRoles(['editor', 'writer']))->toBeTrue()
        ->and($user->hasAllRoles('editor|writer'))->toBeTrue()
        ->and($user->hasAllRoles(['editor', 'missing']))->toBeFalse()
        ->and($user->getRoleNames()->sort()->values()->all())->toBe(['editor', 'writer'])
        ->and(User::query()->role('editor')->pluck('id')->all())->toBe([$user->id])
        ->and(User::query()->role('writer')->pluck('id')->all())->toBe([$user->id])
        ->and(User::query()->withoutRole('editor')->pluck('id')->all())->toBe([$other->id]);
});
