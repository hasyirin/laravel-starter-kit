<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support;

class PermitStrictLetter extends PermitLetter
{
    public function sourceAbility(string $letterAbility): string
    {
        return $letterAbility === 'create' ? 'archive' : parent::sourceAbility($letterAbility);
    }
}
