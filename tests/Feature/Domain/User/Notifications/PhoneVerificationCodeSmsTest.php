<?php

declare(strict_types=1);

use App\Domain\Shared\Sms\Channels\SmsChannel;
use App\Domain\User\Models\User;
use App\Domain\User\Notifications\PhoneVerificationCodeSms;

test('the sms names the app, the code, and when it expires', function (): void {
    config(['app.name' => 'Kit']);
    $user = User::factory()->make();
    $notification = new PhoneVerificationCodeSms('123456');

    expect($notification->via($user))->toBe([SmsChannel::class])
        ->and($notification->toSms($user))->toBe('Your Kit code is 123456. It expires in 10 minutes.');
});
