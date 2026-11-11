<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support;

use App\Domain\Shared\Letter\Support\Field;

class ExtraNoticeLetter extends NoticeLetter
{
    /**
     * @return list<Field>
     */
    public function extraFields(): array
    {
        return [Field::text('remark'), Field::image('stamp')->optional()];
    }
}
