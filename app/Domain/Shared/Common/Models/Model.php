<?php

declare(strict_types=1);

namespace App\Domain\Shared\Common\Models;

use App\Domain\Shared\Common\Concerns\Models\InteractsWithActivityLogs;
use App\Domain\Shared\Common\Concerns\Models\InteractsWithTimestamps;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
abstract class Model extends \Illuminate\Database\Eloquent\Model
{
    use InteractsWithActivityLogs;
    use InteractsWithTimestamps;
    use SoftDeletes;

    /**
     * @param  list<string>  $attributes
     * @return Collection<string, mixed>
     */
    public function toCollection(array $attributes = []): Collection
    {
        return collect(filled($attributes) ? $this->only($attributes) : $this->toArray());
    }
}
