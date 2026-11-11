<?php

declare(strict_types=1);

namespace App\Domain\Shared\Geography\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Geography\Models\District;
use App\Domain\Shared\Geography\Models\Subdistrict;
use App\Domain\Shared\Geography\Support\UniqueKey;

class SaveSubdistrict
{
    use Actionable;

    public function execute(District $district, Subdistrict $subdistrict, string $code, string $name): Subdistrict
    {
        $subdistrict->district()->associate($district);
        $subdistrict->fill(['code' => $code, 'name' => $name]);

        return UniqueKey::save($subdistrict, ['district_id', 'code']);
    }
}
