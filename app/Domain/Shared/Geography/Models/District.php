<?php

declare(strict_types=1);

namespace App\Domain\Shared\Geography\Models;

use App\Domain\Shared\Common\Concerns\Models\InteractsWithAuditLog;
use App\Domain\Shared\Common\Concerns\Models\InteractsWithTimestamps;
use App\Domain\Shared\Geography\Concerns\Models\HoldsLocality;
use App\Domain\Shared\Geography\Concerns\Models\SortsLocalFirst;
use App\Domain\Shared\Geography\Observers\DistrictObserver;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[ObservedBy(DistrictObserver::class)]
class District extends \Hasyirin\Address\Models\District
{
    use HoldsLocality;
    use InteractsWithAuditLog;
    use InteractsWithTimestamps;
    use SortsLocalFirst;

    /**
     * @return Builder<static>
     */
    public static function localRows(): Builder
    {
        if (static::localityCode('district') !== null) {
            return static::query()->local();
        }

        return static::query()->whereIn('state_id', State::localRows()->select('id'));
    }
}
