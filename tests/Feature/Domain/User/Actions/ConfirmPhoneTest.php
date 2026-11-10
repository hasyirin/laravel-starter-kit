<?php

declare(strict_types=1);

use App\Domain\User\Actions\ConfirmPhone;
use App\Domain\User\Models\User;
use App\Domain\User\Support\SmsCode;
use Illuminate\Validation\ValidationException;

test('the right code verifies the phone number', function (): void {
    $user = User::factory()->unverifiedPhone()->create(['phone' => '+60123456789']);
    $code = SmsCode::forVerification('+60123456789')->issue();

    ConfirmPhone::make()->execute($user, $code);

    expect($user->fresh()->hasVerifiedPhone())->toBeTrue();
});

test('a wrong code leaves the phone number unverified', function (): void {
    $user = User::factory()->unverifiedPhone()->create(['phone' => '+60123456789']);
    $code = SmsCode::forVerification('+60123456789')->issue();

    expect(fn () => ConfirmPhone::make()->execute($user, wrongSmsCode($code)))
        ->toThrow(ValidationException::class, 'The code is wrong or has expired.');

    expect($user->fresh()->hasVerifiedPhone())->toBeFalse();
});

test('a code sent to the old number does not verify a new number', function (): void {
    $user = User::factory()->unverifiedPhone()->create(['phone' => '+60123456789']);
    $code = SmsCode::forVerification('+60123456789')->issue();
    $user->forceFill(['phone' => '+60198765432'])->save();

    expect(fn () => ConfirmPhone::make()->execute($user, $code))->toThrow(ValidationException::class);

    expect($user->fresh()->hasVerifiedPhone())->toBeFalse();
});
