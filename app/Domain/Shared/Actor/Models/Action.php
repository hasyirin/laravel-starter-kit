<?php

declare(strict_types=1);

namespace App\Domain\Shared\Actor\Models;

use App\Domain\Shared\Actor\Enums\Act;
use App\Domain\Shared\Actor\Observers\ActionObserver;
use App\Domain\Shared\Common\Concerns\Models\InteractsWithActivityLogs;
use App\Domain\Shared\Common\Concerns\Models\InteractsWithTimestamps;
use App\Domain\User\Models\Profile;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $resource_type
 * @property int $resource_id
 * @property string $actor_type
 * @property int $actor_id
 * @property int|null $profile_id
 * @property Act $name
 * @property CarbonImmutable $acted_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[ObservedBy(ActionObserver::class)]
class Action extends \Hasyirin\Actor\Models\Action
{
    use InteractsWithActivityLogs;
    use InteractsWithTimestamps;

    protected $fillable = [
        'resource_type',
        'resource_id',
        'actor_type',
        'actor_id',
        'profile_id',
        'acted_at',
        'name',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'name' => Act::class,
        ];
    }

    /**
     * @return BelongsTo<Profile, $this>
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
