<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Enums\Feature;
use App\Domain\User\Models\SignInEntry;
use App\Domain\User\Models\User;
use App\Domain\User\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

test('a password reset does not sign in and records no entry', function (): void {
    $this->skipUnlessFeature(Feature::PasswordReset);
    Notification::fake();
    $user = User::factory()->create();
    $this->post(route('password.email'), ['email' => $user->email]);
    $token = null;
    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token): bool {
        $token = $notification->token;

        return true;
    });

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('login'));

    $this->assertGuest();
    expect(SignInEntry::query()->count())->toBe(0);
});
