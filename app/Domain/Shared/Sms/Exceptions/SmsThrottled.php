<?php

declare(strict_types=1);

namespace App\Domain\Shared\Sms\Exceptions;

use RuntimeException;

class SmsThrottled extends RuntimeException
{
    public function __construct(public readonly int $seconds)
    {
        parent::__construct("Too many SMS. Try again in {$seconds} seconds.");
    }
}
