<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support;

class PermitLockedLetter extends PermitExtraLetter
{
    /**
     * @return list<string>
     */
    public function lockedFields(): array
    {
        return ['date', 'remark'];
    }
}
