<?php

declare(strict_types=1);

namespace App\Domain\User\Exceptions;

use RuntimeException;

class SmsCodeThrottled extends RuntimeException
{
    public function __construct(public readonly int $seconds)
    {
        parent::__construct("Too many SMS codes. Try again in {$seconds} seconds.");
    }
}
