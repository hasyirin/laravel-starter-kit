<?php

declare(strict_types=1);

use App\Domain\User\Actions\ConfirmPhone;
use App\Domain\User\Actions\UpdateUserInformation;
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

test('a number change saved after a confirm leaves the new number unverified', function (): void {
    $user = User::factory()->unverifiedPhone()->create(['phone' => '+60123456789']);
    $settings = User::query()->findOrFail($user->id);
    $code = SmsCode::forVerification('+60123456789')->issue();
    ConfirmPhone::make()->execute($user, $code);

    UpdateUserInformation::make()->execute($settings, $settings->name, $settings->email, '+60198765432');

    expect($user->fresh()->phone)->toBe('+60198765432')
        ->and($user->fresh()->hasVerifiedPhone())->toBeFalse();
});

test('a confirm that loaded the old number does not verify a new number', function (): void {
    $user = User::factory()->unverifiedPhone()->create(['phone' => '+60123456789']);
    $confirm = User::query()->findOrFail($user->id);
    $code = SmsCode::forVerification('+60123456789')->issue();
    UpdateUserInformation::make()->execute($user, $user->name, $user->email, '+60198765432');

    expect(fn () => ConfirmPhone::make()->execute($confirm, $code))->toThrow(ValidationException::class);

    expect($user->fresh()->hasVerifiedPhone())->toBeFalse();
});
