<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Letter\Models\Letterhead;

class DeleteLetterhead
{
    use Actionable;

    public function execute(Letterhead $letterhead): void
    {
        $letterhead->delete();
    }
}
