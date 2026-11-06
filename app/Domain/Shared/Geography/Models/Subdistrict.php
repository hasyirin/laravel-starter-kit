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
class Subdistrict extends \Hasyirin\Address\Models\Subdistrict
{
    use InteractsWithAuditLog;
    use InteractsWithTimestamps;
}
