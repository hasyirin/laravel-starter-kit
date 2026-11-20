<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support;

use Tests\Fixtures\Models\Parcel;

class ParcelLetter extends PermitLetter
{
    public function source(): string
    {
        return Parcel::class;
    }
}
