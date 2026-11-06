<?php

declare(strict_types=1);

namespace App\Domain\Shared\Geography\Models;

use App\Domain\Shared\Common\Concerns\Models\InteractsWithAuditLog;
use App\Domain\Shared\Common\Concerns\Models\InteractsWithTimestamps;
use Carbon\CarbonImmutable;

/**
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class PostOffice extends \Hasyirin\Address\Models\PostOffice
{
    use InteractsWithAuditLog;
    use InteractsWithTimestamps;
}
