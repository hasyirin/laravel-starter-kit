<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Models\Role;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;

test('a profile ends when the next profile of its user is created', function (): void {
    $user = User::factory()->create();
    Profile::factory()->for($user)->count(3)->sequence(
        ['created_at' => '2026-01-01 08:00:00'],
        ['created_at' => '2026-03-01 08:00:00'],
        ['created_at' => '2026-06-01 08:00:00'],
    )->create();

    $profiles = $user->profiles()->withEndedAt()->orderBy('id')->get();

    expect($profiles->map(fn (Profile $profile): ?string => $profile->ended_at?->toDateTimeString())->all())
        ->toBe(['2026-03-01 08:00:00', '2026-06-01 08:00:00', null]);
});

test('a profile of another user does not end a profile', function (): void {
    $profile = Profile::factory()->create(['created_at' => '2026-01-01 08:00:00']);
    Profile::factory()->create(['created_at' => '2026-03-01 08:00:00']);

    expect(Profile::query()->withEndedAt()->find($profile->id)?->ended_at)->toBeNull();
});

test('a profile shows the name of its user, also after the user is deleted', function (): void {
    $user = User::factory()->create(['name' => 'Ada Lovelace']);
    $profile = Profile::factory()->for($user)->create();
    $user->delete();

    expect(Profile::query()->find($profile->id)?->name)->toBe('Ada Lovelace');
});

test('the roles of a profile cannot change after it is created', function (): void {
    $profile = Profile::factory()->withRoles(Role::query()->create(['name' => 'editor']))->create();

    expect(fn (): Profile => $profile->syncRoles([]))->toThrow(LogicException::class, 'Roles and permissions can only be given before the first save.')
        ->and($profile->fresh()?->getRoleNames()->all())->toBe(['editor']);
});

test('a profile cannot be deleted', function (string $method): void {
    $profile = Profile::factory()->withRoles(Role::query()->create(['name' => 'editor']))->create();

    expect(fn (): mixed => $profile->{$method}())->toThrow(LogicException::class, 'Profiles cannot be deleted.')
        ->and($profile->fresh()?->trashed())->toBeFalse()
        ->and($profile->fresh()?->roles)->toHaveCount(1);
})->with([
    'soft delete' => 'delete',
    'force delete' => 'forceDelete',
]);
