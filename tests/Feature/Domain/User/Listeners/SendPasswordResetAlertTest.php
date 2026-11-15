<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Enums\Feature;
use App\Domain\User\Models\User;
use App\Domain\User\Notifications\PasswordWasReset;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    $this->skipUnlessFeature(Feature::PasswordReset);
});

test('a reset on the reset page emails the user', function (): void {
    Notification::fake();
    $user = User::factory()->create();

    $this->post(route('password.update'), [
        'token' => Password::broker()->createToken($user),
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertSessionHasNoErrors();

    Notification::assertSentTo($user, PasswordWasReset::class);
});

test('the alert email goes out on the queue, so a mail outage cannot break the reset', function (): void {
    Queue::fake();
    $user = User::factory()->create();

    $this->post(route('password.update'), [
        'token' => Password::broker()->createToken($user),
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertSessionHasNoErrors();

    Queue::assertPushed(SendQueuedNotifications::class, fn (SendQueuedNotifications $job): bool => $job->notification instanceof PasswordWasReset);
});
