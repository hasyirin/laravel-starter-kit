<?php

use App\Domain\User\Models\User;

test('the authenticated user id is added to the context', function (): void {
    $user = User::factory()->create();

    expect(context()->get('user_id'))->toBeNull();

    $this->actingAs($user);

    expect(context()->get('user_id'))->toBe($user->id);
});
