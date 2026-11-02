<?php

declare(strict_types=1);

use App\Domain\Shared\Actor\Enums\Act;
use App\Domain\Shared\Actor\Models\Action;
use App\Domain\User\Models\User;
use Hasyirin\Actor\Facades\Actor;

test('an eager loaded actor includes a deleted user', function (): void {
    $user = User::factory()->create();
    $action = Actor::act(User::factory()->create(), Act::Prepare, $user);
    $user->delete();

    expect(Action::query()->with('actor')->find($action->id)?->actor?->is($user))->toBeTrue();
});
