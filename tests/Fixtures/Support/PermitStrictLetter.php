<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support;

class PermitStrictLetter extends PermitLetter
{
    public function sourceAbility(string $letterAbility): string
    {
        if ($letterAbility === 'create') {
            return 'archive';
        }

        return parent::sourceAbility($letterAbility);
    }
}
