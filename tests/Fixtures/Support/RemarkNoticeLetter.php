<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support;

use App\Domain\Shared\Letter\Support\Field;

class RemarkNoticeLetter extends NoticeLetter
{
    /**
     * @return Field[]
     */
    public function fields(): array
    {
        return [...parent::fields(), Field::text('remark')->optional()];
    }
}
