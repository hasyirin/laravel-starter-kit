<?php

declare(strict_types=1);

namespace App\Domain\Shared\Geography\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Geography\Models\Postcode;
use App\Domain\Shared\Geography\Models\PostOffice;
use App\Domain\Shared\Geography\Support\UniqueKey;

class SavePostcode
{
    use Actionable;

    public function execute(PostOffice $postOffice, Postcode $postcode, string $code, ?float $latitude, ?float $longitude): Postcode
    {
        $postcode->postOffice()->associate($postOffice);
        $postcode->fill(['code' => $code, 'latitude' => $latitude, 'longitude' => $longitude]);

        return UniqueKey::save($postcode, ['post_office_id', 'code']);
    }
}
