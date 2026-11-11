<?php

declare(strict_types=1);

namespace App\Domain\Shared\Geography\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Geography\Models\Country;
use App\Domain\Shared\Geography\Support\UniqueKey;

class SaveCountry
{
    use Actionable;

    public function execute(Country $country, string $code, string $name, ?string $alpha2): Country
    {
        $country->fill(['code' => $code, 'name' => $name, 'alpha_2' => $alpha2]);

        return UniqueKey::save($country, ['code']);
    }
}
