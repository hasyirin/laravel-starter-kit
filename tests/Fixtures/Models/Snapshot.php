<?php

declare(strict_types=1);

namespace Tests\Fixtures\Models;

use App\Domain\Shared\Common\Models\Model;
use App\Domain\Shared\Permission\Concerns\HasFrozenRoles;

class Snapshot extends Model
{
    use HasFrozenRoles;

    protected string $guard_name = 'web';
}
