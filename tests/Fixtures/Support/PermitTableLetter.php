<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support;

use App\Domain\Shared\Letter\Support\Field;

class PermitTableLetter extends PermitLetter
{
    /**
     * @return list<Field>
     */
    public function extraFields(): array
    {
        return [Field::table('lines', [Field::group('detail', 'Detail', [Field::text('note')])])];
    }
}
