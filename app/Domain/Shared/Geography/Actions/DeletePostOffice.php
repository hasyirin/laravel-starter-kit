<?php

declare(strict_types=1);

namespace App\Domain\Shared\Geography\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Geography\Models\PostOffice;
use App\Domain\Shared\Geography\Support\DeleteBlocker;
use LogicException;

class DeletePostOffice
{
    use Actionable;

    public function execute(PostOffice $postOffice): void
    {
        throw_if(DeleteBlocker::blocks($postOffice, null), LogicException::class, 'A city that has postcodes or addresses cannot be deleted.');

        $postOffice->delete();
    }
}
