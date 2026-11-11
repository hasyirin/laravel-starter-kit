<?php

declare(strict_types=1);

namespace App\Domain\Shared\Geography\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Geography\Models\Postcode;

class DeletePostcode
{
    use Actionable;

    public function execute(Postcode $postcode): void
    {
        $postcode->delete();
    }
}
