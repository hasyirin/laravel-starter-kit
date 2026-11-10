<?php

declare(strict_types=1);

use App\Domain\User\Actions\ConfirmPasswordResetCode;
use App\Domain\User\Models\User;
use App\Domain\User\Notifications\SmsPasswordResetStarted;
use App\Domain\User\Support\SmsCode;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

test('the right code gives a reset token and emails the user', function (): void {
    Notification::fake();
    $user = User::factory()->create(['phone' => '+60123456789']);
    $code = SmsCode::forPasswordReset('+60123456789')->issue();

    $reset = ConfirmPasswordResetCode::make()->execute('+60123456789', $code);

    expect($reset['email'])->toBe($user->email)
        ->and(Password::broker()->tokenExists($user, $reset['token']))->toBeTrue();
    Notification::assertSentTo($user, SmsPasswordResetStarted::class);
});

test('a wrong code gives no token', function (): void {
    Notification::fake();
    User::factory()->create(['phone' => '+60123456789']);
    $code = SmsCode::forPasswordReset('+60123456789')->issue();

    expect(fn (): array => ConfirmPasswordResetCode::make()->execute('+60123456789', wrongSmsCode($code)))
        ->toThrow(ValidationException::class, 'The code is wrong or has expired.');

    Notification::assertNothingSent();
});

test('a verification code cannot reset a password', function (): void {
    User::factory()->create(['phone' => '+60123456789']);
    $code = SmsCode::forVerification('+60123456789')->issue();

    expect(fn (): array => ConfirmPasswordResetCode::make()->execute('+60123456789', $code))->toThrow(ValidationException::class);
});
