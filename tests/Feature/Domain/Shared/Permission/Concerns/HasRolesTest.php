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

test('the role helpers and scopes count only the roles of the active profile', function (): void {
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->create(['name' => 'writer']));
    Profile::factory()->for($user)->withRoles(Role::query()->create(['name' => 'editor']), Role::query()->create(['name' => 'author']))->create();
    $other = User::factory()->create();

    expect($user->hasRole('writer'))->toBeFalse()
        ->and($user->hasAllRoles('editor|author'))->toBeTrue()
        ->and($user->hasAllRoles(['editor', 'writer']))->toBeFalse()
        ->and($user->hasExactRoles(['editor', 'author']))->toBeTrue()
        ->and($user->hasExactRoles('editor'))->toBeFalse()
        ->and($user->getRoleNames()->sort()->values()->all())->toBe(['author', 'editor'])
        ->and(User::query()->role('editor')->pluck('id')->all())->toBe([$user->id])
        ->and(User::query()->role('writer')->pluck('id')->all())->toBe([])
        ->and(User::query()->withoutRole('editor')->pluck('id')->all())->toBe([$other->id])
        ->and(User::query()->withoutRole('writer')->pluck('id')->all())->toEqualCanonicalizing([$user->id, $other->id]);
});

test('the roles of a user change only through a profile', function (string $method): void {
    $user = User::factory()->create();
    Role::query()->create(['name' => 'editor']);

    expect(fn (): mixed => $user->{$method}('editor'))->toThrow(LogicException::class, 'Users get roles and permissions only through a profile.')
        ->and($user->roles()->exists())->toBeFalse();
})->with(['assignRole', 'syncRoles', 'removeRole']);
