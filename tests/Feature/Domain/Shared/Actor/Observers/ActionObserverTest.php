<?php

declare(strict_types=1);

use App\Domain\Shared\Actor\Enums\Act;
use App\Domain\Shared\Actor\Models\Action;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use Hasyirin\Actor\Events\ActionRecorded;
use Hasyirin\Actor\Facades\Actor;
use Illuminate\Support\Facades\Event;

test('an action keeps the active profile of the user who acted', function (): void {
    $user = User::factory()->create();
    Profile::factory()->for($user)->create();
    $active = Profile::factory()->for($user)->create();

    $action = Actor::act(User::factory()->create(), Act::Prepare, $user);

    expect($action->profile_id)->toBe($active->id);
});

test('an action keeps its profile after the user gets a new one', function (): void {
    $profile = Profile::factory()->create();
    $action = Actor::act(User::factory()->create(), Act::Prepare, $profile->user);

    Profile::factory()->for($profile->user)->create();

    expect($action->fresh()?->profile_id)->toBe($profile->id);
});

test('acting again takes the active profile of the new actor', function (): void {
    $resource = User::factory()->create();
    Actor::act($resource, Act::Prepare, Profile::factory()->create()->user);
    $profile = Profile::factory()->create();

    $action = Actor::act($resource, Act::Prepare, $profile->user);

    expect($action->profile_id)->toBe($profile->id)
        ->and(Action::query()->count())->toBe(1);
});

test('a listener of a repeat act sees the profile of the new actor', function (): void {
    $resource = User::factory()->create();
    Actor::act($resource, Act::Prepare, Profile::factory()->create()->user);
    $profile = Profile::factory()->create();
    $seen = null;
    Event::listen(function (ActionRecorded $event) use (&$seen): void {
        $seen = $event->action->fresh()?->profile_id;
    });

    Actor::act($resource, Act::Prepare, $profile->user);

    expect($seen)->toBe($profile->id);
});

test('the activity log of a repeat act keeps the profile of the new actor', function (): void {
    $resource = User::factory()->create();
    $old = Profile::factory()->create();
    Actor::act($resource, Act::Prepare, $old->user);
    $new = Profile::factory()->create();

    $action = Actor::act($resource, Act::Prepare, $new->user);

    expect($action->activitiesAsSubject()->latest('id')->first()?->attribute_changes?->toArray())
        ->toMatchArray([
            'attributes' => ['actor_id' => $new->user_id, 'profile_id' => $new->id],
            'old' => ['actor_id' => $old->user_id, 'profile_id' => $old->id],
        ]);
});

test('an action has no profile when the actor has none', function (callable $actor): void {
    expect(Actor::act(User::factory()->create(), Act::Prepare, $actor())->profile_id)->toBeNull();
})->with([
    'a user without a profile' => fn (): User => User::factory()->create(),
    'another model' => fn (): Organization => Organization::factory()->create(),
]);
