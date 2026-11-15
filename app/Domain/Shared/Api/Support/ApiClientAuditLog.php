<?php

declare(strict_types=1);

namespace App\Domain\Shared\Api\Support;

use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\Shared\Api\Models\ApiToken;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\Models\Activity;

class ApiClientAuditLog
{
    /**
     * @return Builder<Activity>
     */
    public static function query(ApiClient $apiClient): Builder
    {
        return self::involving($apiClient)->latest('id');
    }

    public static function find(ApiClient $apiClient, int $id): Activity
    {
        return self::involving($apiClient)->findOrFail($id);
    }

    /**
     * @return Builder<Activity>
     */
    private static function involving(ApiClient $apiClient): Builder
    {
        $tokens = ApiToken::withTrashed()->whereBelongsTo($apiClient)->select('id');

        return Activity::query()
            ->with([
                'causer' => fn (MorphTo $causer): MorphTo => $causer->withTrashed(),
                'subject' => fn (MorphTo $subject): MorphTo => $subject->withTrashed(),
            ])
            ->where(fn (Builder $query): Builder => $query
                ->whereMorphedTo('subject', $apiClient)
                ->orWhere(fn (Builder $query): Builder => $query
                    ->where('subject_type', new ApiToken()->getMorphClass())
                    ->whereIn('subject_id', $tokens)));
    }
}
