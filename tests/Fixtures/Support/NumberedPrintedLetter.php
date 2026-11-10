<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support;

use App\Domain\Shared\Letter\Support\Field;

class NumberedPrintedLetter extends NumberedNoticeLetter
{
    public function view(): string
    {
        return 'tests::letters.printed';
    }

    /**
     * @return Field[]
     */
    public function extraFields(): array
    {
        return [Field::text('remark')];
    }
}
