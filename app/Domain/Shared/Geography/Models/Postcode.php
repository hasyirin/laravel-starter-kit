<?php

declare(strict_types=1);

namespace App\Domain\Shared\Geography\Models;

use App\Domain\Shared\Common\Concerns\Models\InteractsWithAuditLog;
use App\Domain\Shared\Common\Concerns\Models\InteractsWithTimestamps;
use App\Domain\Shared\Geography\Concerns\Models\SortsLocalFirst;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class Postcode extends \Hasyirin\Address\Models\Postcode
{
    use InteractsWithAuditLog;
    use InteractsWithTimestamps;
    use SortsLocalFirst;

    /**
     * @return Builder<static>
     */
    public static function localRows(): Builder
    {
        return static::query()->whereIn('post_office_id', PostOffice::localRows()->select('id'));
    }

    public function auditNameColumn(): string
    {
        return 'code';
    }

    public function auditLabel(): string
    {
        return $this->code;
    }

    /**
     * @return Collection<string, string>
     */
    public static function options(string $search, ?string $current = null, bool $local = false): Collection
    {
        $prefix = str($search)->trim()->upper();

        if (! $local && $prefix->length() < 2) {
            return self::withCurrent(new Collection, $current);
        }

        $postOffices = PostOffice::query()->select('id');

        if ($local) {
            $postOffices->local();
        }

        $query = static::query()->whereIn('post_office_id', $postOffices);

        if ($prefix->isNotEmpty()) {
            $escaped = $prefix->replace(['\\', '%', '_'], ['\\\\', '\%', '\_']);
            $query->where('code', 'like', "{$escaped}%");
        }

        $codes = $query
            ->select('code')
            ->distinct()
            ->orderBy('code')
            ->limit(20)
            ->get()
            ->mapWithKeys(fn (Postcode $postcode): array => [$postcode->code => $postcode->code]);

        return self::withCurrent($codes, $current);
    }

    /**
     * @param  Collection<string, string>  $codes
     * @return Collection<string, string>
     */
    private static function withCurrent(Collection $codes, ?string $current): Collection
    {
        if ($current === null || $current === '' || $codes->has($current)) {
            return $codes;
        }

        return $codes->prepend($current, $current);
    }
}
