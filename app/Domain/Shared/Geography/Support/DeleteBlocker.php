<?php

declare(strict_types=1);

namespace App\Domain\Shared\Geography\Support;

use App\Domain\Shared\Geography\Models\Address;
use App\Domain\Shared\Geography\Models\Country;
use App\Domain\Shared\Geography\Models\District;
use App\Domain\Shared\Geography\Models\PostOffice;
use App\Domain\Shared\Geography\Models\State;
use App\Domain\Shared\Geography\Models\Subdistrict;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeleteBlocker
{
    /**
     * Load this flag and the child counts in a table query, so blocks() runs no query per row.
     *
     * @param  Builder<Country>|Builder<State>|Builder<District>|Builder<Subdistrict>|Builder<PostOffice>  $query
     */
    public static function withAddresses(Builder $query): void
    {
        /** @var Country|State|District|Subdistrict|PostOffice $model */
        $model = $query->getModel();

        $addresses = Address::query();

        $addresses
            ->selectRaw('1')
            ->whereColumn($addresses->qualifyColumn(self::addressColumn($model)), $model->getQualifiedKeyName())
            ->limit(1);

        $query->addSelect(['addresses_exists' => $addresses]);
    }

    public static function blocks(Country|State|District|Subdistrict|PostOffice $record, Country|State|District|null $locality): bool
    {
        return $record->is($locality)
            || self::hasChildren($record)
            || self::hasAddresses($record);
    }

    private static function hasChildren(Country|State|District|Subdistrict|PostOffice $record): bool
    {
        $relations = match (true) {
            $record instanceof Country => ['states_count' => $record->states()],
            $record instanceof State => ['districts_count' => $record->districts(), 'post_offices_count' => $record->postOffices()],
            $record instanceof District => ['subdistricts_count' => $record->subdistricts()],
            $record instanceof Subdistrict => [],
            $record instanceof PostOffice => ['postcodes_count' => $record->postcodes()],
        };

        return collect($relations)->contains(fn (HasMany $relation, string $count): bool => self::hasChild($record, $count, $relation));
    }

    /**
     * @param  HasMany<covariant Model, covariant Model>  $relation
     */
    private static function hasChild(Country|State|District|Subdistrict|PostOffice $record, string $count, HasMany $relation): bool
    {
        if (array_key_exists($count, $record->getAttributes())) {
            return (int) $record->getAttribute($count) > 0;
        }

        return $relation->exists();
    }

    private static function hasAddresses(Country|State|District|Subdistrict|PostOffice $record): bool
    {
        if (array_key_exists('addresses_exists', $record->getAttributes())) {
            return $record->getAttribute('addresses_exists') !== null;
        }

        return Address::query()->where(self::addressColumn($record), $record->getKey())->exists();
    }

    private static function addressColumn(Country|State|District|Subdistrict|PostOffice $record): string
    {
        return match (true) {
            $record instanceof Country => 'country_id',
            $record instanceof State => 'state_id',
            $record instanceof District => 'district_id',
            $record instanceof Subdistrict => 'subdistrict_id',
            $record instanceof PostOffice => 'post_office_id',
        };
    }
}
