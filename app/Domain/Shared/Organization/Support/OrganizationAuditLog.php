<?php

declare(strict_types=1);

namespace App\Domain\Shared\Organization\Support;

use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Organization\Models\Position;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\Models\Activity;

class OrganizationAuditLog
{
    /**
     * @return Builder<Activity>
     */
    public static function query(Organization|Department|Branch $record): Builder
    {
        return self::involving($record)->latest('id');
    }

    public static function find(Organization|Department|Branch $record, int $id): Activity
    {
        return self::involving($record)->findOrFail($id);
    }

    /**
     * @return Builder<Activity>
     */
    private static function involving(Organization|Department|Branch $record): Builder
    {
        $children = match (true) {
            $record instanceof Organization => [
                new Department()->getMorphClass() => $record->departments(),
                new Branch()->getMorphClass() => $record->branches(),
                new Position()->getMorphClass() => $record->positions(),
            ],
            $record instanceof Department => [
                new Position()->getMorphClass() => $record->positions(),
            ],
            default => [],
        };

        return Activity::query()
            ->with(['causer' => fn (MorphTo $causer): MorphTo => $causer->withTrashed()])
            ->where(function (Builder $query) use ($record, $children): void {
                $query->whereMorphedTo('subject', $record);

                foreach ($children as $type => $relation) {
                    /** @var HasMany<Model, Organization|Department> $relation */
                    $query->orWhere(fn (Builder $query): Builder => $query
                        ->where('subject_type', $type)
                        ->whereIn('subject_id', $relation->withTrashed()->select('id')));
                }
            });
    }
}
