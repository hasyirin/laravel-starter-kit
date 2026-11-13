<?php

declare(strict_types=1);

namespace App\Domain\Shared\Common\Enums;

enum Feature: string
{
    case Registration = 'registration';
    case PasswordReset = 'reset-passwords';
    case EmailVerification = 'email-verification';
    case TwoFactorAuthentication = 'two-factor-authentication';
    case Passkeys = 'passkeys';
    case PhoneVerification = 'phone-verification';
    case SmsPasswordReset = 'sms-password-reset';

    public function enabled(): bool
    {
        return in_array($this, config()->array('system.features'), true);
    }

    public function optionEnabled(string $option): bool
    {
        return $this->enabled() && config("system.feature_options.{$this->value}.{$option}") === true;
    }

    public function belongsToFortify(): bool
    {
        return match ($this) {
            self::PhoneVerification, self::SmsPasswordReset => false,
            default => true,
        };
    }

    public static function syncFortify(): void
    {
        config([
            'fortify.features' => collect(self::cases())
                ->filter(fn (self $feature): bool => $feature->belongsToFortify() && $feature->enabled())
                ->map(fn (self $feature): string => $feature->value)
                ->values()
                ->all(),
            'fortify-options' => collect(config()->array('system.feature_options'))
                ->filter(fn (mixed $options, string $feature): bool => self::tryFrom($feature)?->enabled() ?? false)
                ->all(),
        ]);
    }
}
