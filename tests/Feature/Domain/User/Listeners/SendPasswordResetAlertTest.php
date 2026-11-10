<?php

declare(strict_types=1);

use App\Domain\User\Models\User;
use App\Domain\User\Notifications\PasswordWasReset;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

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
