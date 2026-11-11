<?php

declare(strict_types=1);

namespace App\Domain\Shared\Geography\Observers;

use App\Domain\Shared\Geography\Models\Country;
use LogicException;

class CountryObserver
{
    public function updating(Country $country): void
    {
        throw_if($country->isDirty('code') && $country->holdsLocality(), LogicException::class, 'The code of a country that is or contains the locality cannot change.');
    }

    public function deleting(Country $country): void
    {
        throw_if($country->holdsLocality(), LogicException::class, 'A country that is or contains the locality cannot be deleted.');
    }
}
