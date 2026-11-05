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

test('the role helpers and scopes count the direct grants and the active profile', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::query()->create(['name' => 'writer']));
    Profile::factory()->for($user)->withRoles(Role::query()->create(['name' => 'editor']))->create();
    $other = User::factory()->create();

    expect($user->hasRole('writer'))->toBeTrue()
        ->and($user->hasAllRoles('editor|writer'))->toBeTrue()
        ->and($user->hasAllRoles(['editor', 'missing']))->toBeFalse()
        ->and($user->hasExactRoles(['editor', 'writer']))->toBeTrue()
        ->and($user->hasExactRoles('editor'))->toBeFalse()
        ->and($user->getRoleNames()->all())->toEqualCanonicalizing(['editor', 'writer'])
        ->and(User::query()->role('editor')->pluck('id')->all())->toBe([$user->id])
        ->and(User::query()->role('writer')->pluck('id')->all())->toBe([$user->id])
        ->and(User::query()->withoutRole('editor')->pluck('id')->all())->toBe([$other->id])
        ->and(User::query()->withoutRole('writer')->pluck('id')->all())->toBe([$other->id]);
});

test('a user can get and lose roles directly', function (string $method, string $role, array $expected): void {
    $user = User::factory()->create();
    $user->assignRole(Role::query()->create(['name' => 'writer']));
    Role::query()->create(['name' => 'editor']);

    $user->{$method}($role);

    expect($user->fresh()?->getRoleNames()->all())->toEqualCanonicalizing($expected);
})->with([
    ['assignRole', 'editor', ['editor', 'writer']],
    ['syncRoles', 'editor', ['editor']],
    ['removeRole', 'writer', []],
]);
