<?php

declare(strict_types=1);

namespace App\Domain\Shared\Geography\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Geography\Models\Subdistrict;
use App\Domain\Shared\Geography\Support\DeleteBlocker;
use LogicException;

class DeleteSubdistrict
{
    use Actionable;

    public function execute(Subdistrict $subdistrict): void
    {
        throw_if(DeleteBlocker::blocks($subdistrict, null), LogicException::class, 'A subdistrict that has addresses cannot be deleted.');

        $subdistrict->delete();
    }
}
