<?php

declare(strict_types=1);

use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;

test('the newest profile is the active profile', function (): void {
    $user = User::factory()->create();
    Profile::factory()->for($user)->create();
    $newest = Profile::factory()->for($user)->create();

    expect($user->profile?->id)->toBe($newest->id);
});

test('the profiles of a user include the inactive profiles', function (): void {
    $user = User::factory()->create();
    Profile::factory()->for($user)->count(2)->create();

    expect($user->profiles)->toHaveCount(2);
});

test('the active profile loads when it is read', function (): void {
    $profile = Profile::factory()->create();

    $user = User::query()->find($profile->user_id);

    expect($user?->relationLoaded('profile'))->toBeFalse()
        ->and($user?->profile?->id)->toBe($profile->id);
});

test('the profiles of a user hold the same user', function (): void {
    $profile = Profile::factory()->create();

    $user = User::query()->find($profile->user_id);

    expect($user?->profile?->user)->toBe($user)
        ->and($user?->profiles->first()?->user)->toBe($user);
});
