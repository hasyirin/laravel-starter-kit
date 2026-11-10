<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Letter\Models\Letter;

class DeleteLetter
{
    use Actionable;

    public function execute(Letter $letter): void
    {
        $letter->delete();
    }
}
