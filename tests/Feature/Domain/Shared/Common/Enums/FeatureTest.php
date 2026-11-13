<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Enums\Feature;
use Laravel\Fortify\Features;

test('a feature is on when system.features lists it', function (): void {
    config(['system.features' => [Feature::Passkeys]]);

    expect(Feature::Passkeys->enabled())->toBeTrue()
        ->and(Feature::TwoFactorAuthentication->enabled())->toBeFalse();
});

test('an option is on only when it is true and its feature is on', function (): void {
    config([
        'system.features' => [Feature::TwoFactorAuthentication],
        'system.feature_options' => [Feature::TwoFactorAuthentication->value => ['confirm' => true, 'confirmPassword' => false]],
    ]);

    expect(Feature::TwoFactorAuthentication->optionEnabled('confirm'))->toBeTrue()
        ->and(Feature::TwoFactorAuthentication->optionEnabled('confirmPassword'))->toBeFalse();

    config(['system.features' => []]);

    expect(Feature::TwoFactorAuthentication->optionEnabled('confirm'))->toBeFalse();
});

test('fortify gets the fortify features that are on, with their options', function (): void {
    config([
        'system.features' => [Feature::Registration, Feature::TwoFactorAuthentication, Feature::PhoneVerification],
        'system.feature_options' => [Feature::TwoFactorAuthentication->value => ['confirm' => true]],
    ]);

    Feature::syncFortify();

    expect(config('fortify.features'))->toBe([Features::registration(), Features::twoFactorAuthentication()])
        ->and(Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm'))->toBeTrue();
});

test('fortify gets no options of a feature that is off', function (): void {
    config([
        'system.features' => [Feature::TwoFactorAuthentication],
        'system.feature_options' => [Feature::Passkeys->value => ['confirmPassword' => false]],
    ]);

    Feature::syncFortify();

    expect(config('fortify-options'))->toBe([]);
});

test('enabling a feature that fortify does not own is refused', function (): void {
    expect(fn () => $this->enableFeature(Feature::PhoneVerification))->toThrow(LogicException::class, 'enableFeature() reloads only the Fortify routes, so it cannot turn on [phone-verification].');
});

test('every fortify case has the name that fortify uses', function (): void {
    expect([Feature::Registration->value, Feature::PasswordReset->value, Feature::EmailVerification->value, Feature::TwoFactorAuthentication->value, Feature::Passkeys->value])
        ->toBe([Features::registration(), Features::resetPasswords(), Features::emailVerification(), Features::twoFactorAuthentication(), Features::passkeys()]);
});
