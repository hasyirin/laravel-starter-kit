<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support;

use App\Domain\Shared\Letter\Support\Field;

class PermitYesNoLetter extends PermitLetter
{
    /**
     * @return list<Field>
     */
    public function extraFields(): array
    {
        return [
            Field::yesNo('urgent'),
            Field::group('guarantor', 'Guarantor', [Field::text('name'), Field::yesNo('local')]),
        ];
    }
}
