<?php

declare(strict_types=1);

use App\Domain\User\Actions\SendPhoneVerificationCode;
use App\Domain\User\Exceptions\SmsCodeThrottled;
use App\Domain\User\Models\User;
use App\Domain\User\Notifications\PhoneVerificationCodeSms;
use App\Domain\User\Support\SmsCode;
use Illuminate\Support\Facades\Notification;

test('sending a code texts a new code to the number of the user', function (): void {
    Notification::fake();
    $user = User::factory()->unverifiedPhone()->create(['phone' => '+60123456789']);

    SendPhoneVerificationCode::make()->execute($user);

    Notification::assertSentTo($user, PhoneVerificationCodeSms::class,
        fn (PhoneVerificationCodeSms $notification): bool => SmsCode::forVerification('+60123456789')->check($notification->code));
});

test('a user can get one code per minute and five per hour', function (): void {
    Notification::fake();
    $user = User::factory()->unverifiedPhone()->create();

    SendPhoneVerificationCode::make()->execute($user);
    expect(fn () => SendPhoneVerificationCode::make()->execute($user))->toThrow(SmsCodeThrottled::class);

    foreach (range(2, 5) as $sms) {
        $this->travel(60)->seconds();
        SendPhoneVerificationCode::make()->execute($user);
    }

    $this->travel(60)->seconds();
    expect(fn () => SendPhoneVerificationCode::make()->execute($user))->toThrow(SmsCodeThrottled::class);
    Notification::assertSentToTimes($user, PhoneVerificationCodeSms::class, 5);
});

test('a user without a phone number gets no code', function (): void {
    Notification::fake();
    $user = User::factory()->withoutPhone()->create();

    SendPhoneVerificationCode::make()->execute($user);

    Notification::assertNothingSent();
});
