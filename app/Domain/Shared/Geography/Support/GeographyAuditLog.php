<?php

declare(strict_types=1);

namespace App\Domain\Shared\Geography\Support;

use App\Domain\Shared\Geography\Models\Country;
use App\Domain\Shared\Geography\Models\District;
use App\Domain\Shared\Geography\Models\Postcode;
use App\Domain\Shared\Geography\Models\PostOffice;
use App\Domain\Shared\Geography\Models\State;
use App\Domain\Shared\Geography\Models\Subdistrict;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\Models\Activity;

class GeographyAuditLog
{
    /**
     * @return Builder<Activity>
     */
    public static function query(Country|State|District|PostOffice $record): Builder
    {
        return self::involving($record)->latest('id');
    }

    public static function find(Country|State|District|PostOffice $record, int $id): Activity
    {
        return self::involving($record)->findOrFail($id);
    }

    /**
     * @return Builder<Activity>
     */
    private static function involving(Country|State|District|PostOffice $record): Builder
    {
        $children = match (true) {
            $record instanceof Country => [
                new State()->getMorphClass() => State::withTrashed()->whereBelongsTo($record)->select('id'),
            ],
            $record instanceof State => [
                new District()->getMorphClass() => District::withTrashed()->whereBelongsTo($record)->select('id'),
                new PostOffice()->getMorphClass() => PostOffice::withTrashed()->whereBelongsTo($record)->select('id'),
            ],
            $record instanceof District => [
                new Subdistrict()->getMorphClass() => Subdistrict::withTrashed()->whereBelongsTo($record)->select('id'),
            ],
            $record instanceof PostOffice => [
                new Postcode()->getMorphClass() => Postcode::withTrashed()->whereBelongsTo($record)->select('id'),
            ],
        };

        return Activity::query()
            ->with([
                'causer' => fn (MorphTo $causer): MorphTo => $causer->withTrashed(),
                'subject' => fn (MorphTo $subject): MorphTo => $subject->withTrashed(),
            ])
            ->where(function (Builder $query) use ($record, $children): void {
                $query->whereMorphedTo('subject', $record);

                foreach ($children as $type => $ids) {
                    $query->orWhere(fn (Builder $query): Builder => $query
                        ->where('subject_type', $type)
                        ->whereIn('subject_id', $ids));
                }
            });
    }
}
