<?php

declare(strict_types=1);

namespace App\Domain\Shared\Geography\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Geography\Models\Country;
use App\Domain\Shared\Geography\Models\State;
use App\Domain\Shared\Geography\Support\UniqueKey;

class SaveState
{
    use Actionable;

    public function execute(Country $country, State $state, string $code, string $name): State
    {
        $state->country()->associate($country);
        $state->fill(['code' => $code, 'name' => $name]);

        return UniqueKey::save($state, ['country_id', 'code']);
    }
}
