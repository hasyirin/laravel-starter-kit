<?php

declare(strict_types=1);

namespace App\Domain\Shared\Permission\Models;

use App\Domain\Shared\Common\Concerns\Models\InteractsWithTimestamps;

class Permission extends \Spatie\Permission\Models\Permission
{
    use InteractsWithTimestamps;
}
