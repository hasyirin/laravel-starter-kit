<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Enums\Feature;
use App\Domain\User\Models\User;
use App\Domain\User\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    $this->skipUnlessFeature(Feature::PasswordReset);
});

test('reset password link screen can be rendered', function (): void {
    $response = $this->get(route('password.request'));

    $response->assertOk();
});

test('reset password link can be requested', function (): void {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.request'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class);
});

test('reset password screen can be rendered', function (): void {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.request'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification): true {
        $response = $this->get(route('password.reset', $notification->token));

        $response->assertOk();

        return true;
    });
});

test('password can be reset with valid token', function (): void {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.request'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user): true {
        $response = $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login', absolute: false));

        return true;
    });
});

test('a guest can request a reset link five times a minute', function (): void {
    foreach (range(1, 5) as $attempt) {
        $this->post(route('password.email'), ['email' => 'nobody@example.com'])->assertRedirect();
    }

    $this->post(route('password.email'), ['email' => 'nobody@example.com'])->assertTooManyRequests();
});

test('the forgot password page hides the sms reset link when the sms reset is off', function (): void {
    $this->disableFeature(Feature::SmsPasswordReset);

    $this->get(route('password.request'))->assertOk()->assertDontSee(__('auth/forgot-password.sms'));
});
