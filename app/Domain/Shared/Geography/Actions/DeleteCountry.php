<?php

declare(strict_types=1);

namespace App\Domain\Shared\Geography\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Geography\Models\Country;
use App\Domain\Shared\Geography\Support\DeleteBlocker;
use LogicException;

class DeleteCountry
{
    use Actionable;

    public function execute(Country $country): void
    {
        throw_if(DeleteBlocker::blocks($country, Country::local()), LogicException::class, 'A country that is or contains the locality, has states, or has addresses cannot be deleted.');

        $country->delete();
    }
}
