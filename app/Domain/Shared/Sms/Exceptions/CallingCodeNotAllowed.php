<?php

declare(strict_types=1);

namespace App\Domain\Shared\Sms\Exceptions;

use LogicException;

class CallingCodeNotAllowed extends LogicException
{
    public function __construct()
    {
        parent::__construct('The app sends SMS only to the calling codes in system.sms.calling_codes.');
    }
}
