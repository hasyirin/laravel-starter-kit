<?php

declare(strict_types=1);

namespace App\Domain\Shared\Geography\Observers;

use App\Domain\Shared\Geography\Models\District;
use LogicException;

class DistrictObserver
{
    public function updating(District $district): void
    {
        throw_if($district->isDirty('code') && $district->holdsLocality(), LogicException::class, 'The code of a district that is or contains the locality cannot change.');
    }

    public function deleting(District $district): void
    {
        throw_if($district->holdsLocality(), LogicException::class, 'A district that is or contains the locality cannot be deleted.');
    }
}
