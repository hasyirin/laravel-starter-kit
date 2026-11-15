<?php

declare(strict_types=1);

use App\Domain\User\Actions\Security\ConfirmPasswordResetCode;
use App\Domain\User\Exceptions\SmsPasswordResetLocked;
use App\Domain\User\Models\User;
use App\Domain\User\Notifications\SmsPasswordResetStarted;
use App\Domain\User\Support\SmsCode;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Queue;
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

test('the alert email goes out on the queue, so a mail outage cannot stop the reset', function (): void {
    Queue::fake();
    User::factory()->create(['phone' => '+60123456789']);
    $code = SmsCode::forPasswordReset('+60123456789')->issue();

    ConfirmPasswordResetCode::make()->execute('+60123456789', $code);

    Queue::assertPushed(SendQueuedNotifications::class, fn (SendQueuedNotifications $job): bool => $job->notification instanceof SmsPasswordResetStarted);
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

test('ten wrong codes lock the sms reset of a number for a day, also for a number without an account', function (): void {
    foreach (range(1, 10) as $try) {
        expect(fn (): array => ConfirmPasswordResetCode::make()->execute('+60123456789', '000000'))->toThrow(ValidationException::class);
    }

    expect(fn (): array => ConfirmPasswordResetCode::make()->execute('+60123456789', '000000'))->toThrow(SmsPasswordResetLocked::class)
        ->and(fn (): array => ConfirmPasswordResetCode::make()->execute('+60198765432', '000000'))->toThrow(ValidationException::class);

    $this->travel(23)->hours();

    expect(fn (): array => ConfirmPasswordResetCode::make()->execute('+60123456789', '000000'))->toThrow(SmsPasswordResetLocked::class);

    $this->travel(1)->hour();

    expect(fn (): array => ConfirmPasswordResetCode::make()->execute('+60123456789', '000000'))->toThrow(ValidationException::class);
});

test('a locked number refuses even the right code', function (): void {
    User::factory()->create(['phone' => '+60123456789']);
    lockSmsPasswordReset('+60123456789');
    $code = SmsCode::forPasswordReset('+60123456789')->issue();

    expect(fn (): array => ConfirmPasswordResetCode::make()->execute('+60123456789', $code))->toThrow(SmsPasswordResetLocked::class);
});
