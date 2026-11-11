<?php

declare(strict_types=1);

namespace App\Domain\Shared\Geography\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Geography\Models\District;
use App\Domain\Shared\Geography\Models\State;
use App\Domain\Shared\Geography\Support\UniqueKey;

class SaveDistrict
{
    use Actionable;

    public function execute(State $state, District $district, string $code, string $name): District
    {
        $district->state()->associate($state);
        $district->fill(['code' => $code, 'name' => $name]);

        return UniqueKey::save($district, ['state_id', 'code']);
    }
}
