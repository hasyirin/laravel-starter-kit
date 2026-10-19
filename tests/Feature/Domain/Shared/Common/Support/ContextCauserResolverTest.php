<?php

use App\Domain\Shared\Permission\Models\Role;
use App\Domain\User\Models\User;
use Spatie\Activitylog\Models\Activity;

test('the causer is the logged-in user', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);
    Role::query()->create(['name' => 'editor']);

    expect(Activity::query()->where('subject_type', 'role')->sole()->causer_id)->toBe($user->id);
});

test('the causer is the user in the context when no one is logged in', function (): void {
    $user = User::factory()->create();

    context()->add('user_id', $user->id);
    Role::query()->create(['name' => 'editor']);

    expect(auth()->user())->toBeNull()
        ->and(Activity::query()->where('subject_type', 'role')->sole()->causer_id)->toBe($user->id);
});

test('the causer is the user in the context even when that user is deleted', function (): void {
    $user = User::factory()->create();
    $user->delete();

    context()->add('user_id', $user->id);
    Role::query()->create(['name' => 'editor']);

    expect(Activity::query()->where('subject_type', 'role')->sole()->causer_id)->toBe($user->id);
});

test('there is no causer without a logged-in user or a user in the context', function (): void {
    Role::query()->create(['name' => 'editor']);

    expect(Activity::query()->where('subject_type', 'role')->sole()->causer_id)->toBeNull();
});

test('an explicit causer still wins', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();

    context()->add('user_id', $user->id);
    activity()->causedBy($other)->log('Did something');

    expect(Activity::query()->where('description', 'Did something')->sole()->causer_id)->toBe($other->id);
});
