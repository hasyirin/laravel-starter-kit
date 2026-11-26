<?php

declare(strict_types=1);

use App\Domain\User\Actions\Security\ResetForgottenPassword;
use App\Domain\User\Actions\Session\EndAllSessions;
use App\Domain\User\Enums\SignInEvent;
use App\Domain\User\Models\SignInEntry;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserSession;

test('a password reset ends all sessions of the user, with the user as who ended them', function (): void {
    $user = User::factory()->create();
    UserSession::factory()->count(2)->forUser($user)->create();
    UserSession::factory()->impersonatedBy($user)->create();
    $other = UserSession::factory()->create();

    ResetForgottenPassword::make()->reset($user, ['password' => 'new-password', 'password_confirmation' => 'new-password']);

    expect(UserSession::query()->pluck('id')->all())->toBe([$other->id])
        ->and(SignInEntry::query()->where('event', SignInEvent::SessionEnded)->pluck('ended_by_id')->all())->toBe([$user->id, $user->id, $user->id]);
});

test('a password reset that fails to end the sessions keeps the old password', function (): void {
    $user = User::factory()->create();
    $oldHash = $user->password;
    $this->mock(EndAllSessions::class)->shouldReceive('execute')->andThrow(new RuntimeException('Ending failed.'));

    expect(fn () => ResetForgottenPassword::make()->reset($user, ['password' => 'new-password', 'password_confirmation' => 'new-password']))->toThrow(RuntimeException::class, 'Ending failed.');

    expect($user->fresh()->password)->toBe($oldHash);
});
