<?php

declare(strict_types=1);

namespace App\Domain\Shared\Geography\Models;

use App\Domain\Shared\Common\Concerns\Models\InteractsWithAuditLog;
use App\Domain\Shared\Common\Concerns\Models\InteractsWithTimestamps;
use App\Domain\Shared\Geography\Concerns\Models\SortsLocalFirst;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class PostOffice extends \Hasyirin\Address\Models\PostOffice
{
    use InteractsWithAuditLog;
    use InteractsWithTimestamps;
    use SortsLocalFirst;

    /**
     * @return Builder<static>
     */
    public static function localRows(): Builder
    {
        return static::query()->whereIn('state_id', State::localRows()->select('id'));
    }
}
