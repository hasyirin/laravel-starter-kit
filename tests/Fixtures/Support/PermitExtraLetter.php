<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support;

use App\Domain\Shared\Letter\Support\Field;

class PermitExtraLetter extends PermitLetter
{
    /**
     * @return list<Field>
     */
    public function extraFields(): array
    {
        return [
            Field::text('remark'),
            Field::group('guarantor', 'Guarantor', [Field::text('name'), Field::text('phone')]),
            Field::image('stamp')->optional(),
        ];
    }
}
