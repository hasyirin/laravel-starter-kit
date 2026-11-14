<?php

declare(strict_types=1);

namespace App\Domain\Shared\Api\Enums;

use App\Domain\Shared\Common\Concerns\Enums\EnumExtension;
use App\Domain\Shared\Common\Concerns\Enums\InteractsWithColor;
use App\Domain\Shared\Common\Concerns\Enums\InteractsWithLabel;
use App\Domain\Shared\Common\Contracts\Enums\HasColor;
use App\Domain\Shared\Common\Contracts\Enums\HasLabel;

enum ApiCallOutcome: string implements HasColor, HasLabel
{
    use EnumExtension, InteractsWithColor, InteractsWithLabel;

    case Ok = 'ok';
    case MissingToken = 'missing_token';
    case InvalidToken = 'invalid_token';
    case ExpiredToken = 'expired_token';
    case RevokedToken = 'revoked_token';
    case ClientDisabled = 'client_disabled';
    case IpNotAllowed = 'ip_not_allowed';
    case MissingScope = 'missing_scope';
    case RateLimited = 'rate_limited';
    case ValidationFailed = 'validation_failed';
    case ClientError = 'client_error';
    case ServerError = 'server_error';
    case ConnectionFailed = 'connection_failed';

    public function label(): string
    {
        return (string) __("api/api-calls.outcomes.$this->value");
    }

    public function color(): string
    {
        return match ($this) {
            self::Ok => 'green',
            self::RateLimited, self::ValidationFailed, self::ClientError => 'amber',
            self::MissingToken, self::InvalidToken, self::ExpiredToken, self::RevokedToken, self::ClientDisabled, self::IpNotAllowed, self::MissingScope, self::ServerError, self::ConnectionFailed => 'red',
        };
    }

    public static function fromStatus(int $status): self
    {
        return match (true) {
            $status < 400 => self::Ok,
            $status === 422 => self::ValidationFailed,
            $status === 429 => self::RateLimited,
            $status < 500 => self::ClientError,
            default => self::ServerError,
        };
    }
}
