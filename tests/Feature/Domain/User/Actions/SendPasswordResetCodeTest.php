<?php

declare(strict_types=1);

use App\Domain\User\Actions\SendPasswordResetCode;
use App\Domain\User\Exceptions\SmsCodeThrottled;
use App\Domain\User\Models\User;
use App\Domain\User\Notifications\PasswordResetCode;
use App\Domain\User\Support\SmsCode;
use Illuminate\Support\Facades\Notification;

test('a verified number gets a reset code', function (): void {
    Notification::fake();
    $user = User::factory()->create(['phone' => '+60123456789']);

    SendPasswordResetCode::make()->execute('+60123456789', '127.0.0.1');

    Notification::assertSentTo($user, PasswordResetCode::class,
        fn (PasswordResetCode $notification): bool => SmsCode::forPasswordReset('+60123456789')->check($notification->code));
});

test('no reset code goes to a number that is unknown, unverified, deleted, or outside the calling codes', function (Closure $makeUser, string $phone): void {
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

test('each number and each address can ask for one reset code per minute, also for an unknown number', function (): void {
    SendPasswordResetCode::make()->execute('+60123456789', '127.0.0.1');

    expect(fn () => SendPasswordResetCode::make()->execute('+60123456789', '127.0.0.2'))->toThrow(SmsCodeThrottled::class)
        ->and(fn () => SendPasswordResetCode::make()->execute('+60198765432', '127.0.0.1'))->toThrow(SmsCodeThrottled::class);
});
