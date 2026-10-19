<?php

declare(strict_types=1);

namespace Tests\Fixtures\Policies;

use App\Domain\Shared\Common\Policies\Policy;
use Tests\Fixtures\Models\Item;

class ArchivedItemPolicy extends Policy
{
    protected static string $prefix = 'archive';

    protected static ?string $model = Item::class;
}
