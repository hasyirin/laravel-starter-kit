<?php

declare(strict_types=1);

namespace App\Domain\Shared\Geography\Support;

use App\Domain\Shared\Geography\Models\Country;
use App\Domain\Shared\Geography\Models\District;
use App\Domain\Shared\Geography\Models\Postcode;
use App\Domain\Shared\Geography\Models\PostOffice;
use App\Domain\Shared\Geography\Models\State;
use App\Domain\Shared\Geography\Models\Subdistrict;

class UniqueKey
{
    /**
     * The unique indexes of the geography tables include trashed rows. So a new record with the key of a trashed row restores that row.
     *
     * @template TModel of Country|State|District|Subdistrict|PostOffice|Postcode
     *
     * @param  TModel  $record
     * @param  list<string>  $columns
     * @return TModel
     */
    public static function save(Country|State|District|Subdistrict|PostOffice|Postcode $record, array $columns): Country|State|District|Subdistrict|PostOffice|Postcode
    {
        $trashed = self::trashed($record, $columns);

        if ($trashed === null) {
            $record->save();

            return $record;
        }

        $trashed->fill($record->getAttributes());
        $trashed->restore();

        return $trashed;
    }

    /**
     * @template TModel of Country|State|District|Subdistrict|PostOffice|Postcode
     *
     * @param  TModel  $record
     * @param  list<string>  $columns
     * @return TModel|null
     */
    private static function trashed(Country|State|District|Subdistrict|PostOffice|Postcode $record, array $columns): Country|State|District|Subdistrict|PostOffice|Postcode|null
    {
        if ($record->exists) {
            return null;
        }

        $key = collect($columns)->mapWithKeys(fn (string $column): array => [$column => $record->getAttribute($column)]);

        /** @var TModel|null $trashed */
        $trashed = $record->newQuery()->onlyTrashed()->where($key->all())->first();

        return $trashed;
    }
}
