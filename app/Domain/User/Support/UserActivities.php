<?php

declare(strict_types=1);

namespace App\Domain\User\Support;

use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\Models\Activity;

class UserActivities
{
    /**
     * @return Builder<Activity>
     */
    public static function query(User $user, string $direction): Builder
    {
        return self::involving($user, by: $direction !== 'on', on: $direction === 'on')->latest('id');
    }

    public static function find(User $user, int $id): Activity
    {
        return self::involving($user, by: true, on: true)->findOrFail($id);
    }

    /**
     * @return Builder<Activity>
     */
    private static function involving(User $user, bool $by, bool $on): Builder
    {
        return Activity::query()
            ->with(['causer', 'subject' => fn (MorphTo $subject): MorphTo => $subject->morphWith([Profile::class => ['user']])])
            ->where(function (Builder $query) use ($user, $by, $on): void {
                if ($by) {
                    $query->orWhere(fn (Builder $query): Builder => $query->whereMorphedTo('causer', $user));
                }

                if ($on) {
                    $query->orWhere(fn (Builder $query): Builder => $query->whereMorphedTo('subject', $user))
                        ->orWhere(fn (Builder $query): Builder => $query
                            ->where('subject_type', new Profile()->getMorphClass())
                            ->whereIn('subject_id', Profile::query()->select('id')->whereBelongsTo($user)));
                }
            });
    }
}
