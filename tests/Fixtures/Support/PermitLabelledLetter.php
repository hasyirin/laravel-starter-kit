<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support;

class PermitLabelledLetter extends PermitLetter
{
    public function label(): string
    {
        return 'Permit Approval';
    }
}
