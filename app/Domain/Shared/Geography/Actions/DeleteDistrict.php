<?php

declare(strict_types=1);

namespace App\Domain\Shared\Geography\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Geography\Models\District;
use App\Domain\Shared\Geography\Support\DeleteBlocker;
use LogicException;

class DeleteDistrict
{
    use Actionable;

    public function execute(District $district): void
    {
        throw_if(DeleteBlocker::blocks($district, District::local()), LogicException::class, 'A district that is the locality, has subdistricts, or has addresses cannot be deleted.');

        $district->delete();
    }
}
