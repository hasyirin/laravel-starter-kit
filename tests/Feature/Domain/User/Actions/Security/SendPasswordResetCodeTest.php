<?php

declare(strict_types=1);

use App\Domain\User\Actions\Security\SendPasswordResetCode;
use App\Domain\User\Exceptions\SmsCodeThrottled;
use App\Domain\User\Exceptions\SmsPasswordResetLocked;
use App\Domain\User\Models\User;
use App\Domain\User\Notifications\PasswordResetCodeSms;
use App\Domain\User\Support\SmsCode;
use Illuminate\Support\Defer\DeferredCallbackCollection;
use Illuminate\Support\Facades\Notification;

test('a verified number gets a reset code', function (): void {
    $this->withoutDefer();
    Notification::fake();
    $user = User::factory()->create(['phone' => '+60123456789']);

    SendPasswordResetCode::make()->execute('+60123456789', '127.0.0.1');

    Notification::assertSentTo($user, PasswordResetCodeSms::class,
        fn (PasswordResetCodeSms $notification): bool => SmsCode::forPasswordReset('+60123456789')->check($notification->code));
});

test('no reset code goes to a number that is unknown, unverified, deleted, or outside the calling codes', function (Closure $makeUser, string $phone): void {
    $this->withoutDefer();
    Notification::fake();
    $makeUser();

    SendPasswordResetCode::make()->execute($phone, '127.0.0.1');

    Notification::assertNothingSent();
})->with([
    'unknown' => [fn (): null => null, '+60123456789'],
    'unverified' => [fn () => User::factory()->unverifiedPhone()->create(['phone' => '+60123456789']), '+60123456789'],
    'deleted' => [fn () => User::factory()->create(['phone' => '+60123456789'])->delete(), '+60123456789'],
    'outside the calling codes' => [fn () => User::factory()->create(['phone' => '+6591234567']), '+6591234567'],
]);

test('the reset code goes out after the response, so every number gets the same response time', function (): void {
    Notification::fake();
    $user = User::factory()->create(['phone' => '+60123456789']);

    SendPasswordResetCode::make()->execute('+60123456789', '127.0.0.1');

    Notification::assertNothingSent();

    resolve(DeferredCallbackCollection::class)->invoke();

    Notification::assertSentTo($user, PasswordResetCodeSms::class);
});

test('no reset code goes to a locked number', function (): void {
    User::factory()->create(['phone' => '+60123456789']);
    lockSmsPasswordReset('+60123456789');

    expect(fn () => SendPasswordResetCode::make()->execute('+60123456789', '127.0.0.1'))->toThrow(SmsPasswordResetLocked::class);
});

test('each number and each address can ask for one reset code per minute, also for an unknown number', function (): void {
    SendPasswordResetCode::make()->execute('+60123456789', '127.0.0.1');

    expect(fn () => SendPasswordResetCode::make()->execute('+60123456789', '127.0.0.2'))->toThrow(SmsCodeThrottled::class)
        ->and(fn () => SendPasswordResetCode::make()->execute('+60198765432', '127.0.0.1'))->toThrow(SmsCodeThrottled::class);
});
