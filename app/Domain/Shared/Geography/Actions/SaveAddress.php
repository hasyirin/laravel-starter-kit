<?php

declare(strict_types=1);

namespace App\Domain\Shared\Geography\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Geography\Models\Address;
use App\Domain\Shared\Geography\Models\District;
use App\Domain\Shared\Geography\Models\PostOffice;
use Hasyirin\Address\Contracts\Addressable;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class SaveAddress
{
    use Actionable;

    /**
     * @param  array{line_1?: ?string, line_2?: ?string, line_3?: ?string, postcode?: ?string, post_office_id?: ?int, district_id?: ?int, subdistrict_id?: ?int, latitude?: ?float, longitude?: ?float}  $values
     */
    public function execute(Model&Addressable $addressable, array $values, string|UnitEnum $type = 'primary'): ?Address
    {
        $saved = $addressable->address($type)->first();

        if (collect($values)->every(fn (mixed $value): bool => blank($value))) {
            $saved?->delete();

            return null;
        }

        $postOffice = PostOffice::query()->with('state')->findOrFail($values['post_office_id'] ?? null);

        $attributes = [
            ...$values,
            'state_id' => $postOffice->state_id,
            'country_id' => $postOffice->state->country_id,
        ];

        $hiddenDistrict = ! array_key_exists('district_id', $values);
        $districtId = $hiddenDistrict ? $saved?->district_id : $values['district_id'];

        // The state is the only check of a district against the city. So a hidden district is also cleared when the city changes.
        if (! $this->inState($districtId, $postOffice->state_id) || ($hiddenDistrict && $this->changed($saved, $values, ['post_office_id']))) {
            $attributes = [...$attributes, 'district_id' => null, 'subdistrict_id' => null];
        }

        // Nothing can check a hidden location against the address. So the location is cleared when the address moves.
        if (! array_key_exists('latitude', $values) && $this->changed($saved, $values, ['line_1', 'line_2', 'line_3', 'postcode', 'post_office_id'])) {
            $attributes = [...$attributes, 'latitude' => null, 'longitude' => null];
        }

        $address = $addressable->address($type)->updateOrCreate([], $attributes);

        assert($address instanceof Address);

        return $address;
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  list<string>  $keys
     */
    private function changed(?\Hasyirin\Address\Models\Address $saved, array $values, array $keys): bool
    {
        if (! $saved instanceof \Hasyirin\Address\Models\Address) {
            return false;
        }

        return collect($keys)
            ->contains(fn (string $key): bool => (string) $saved->getAttribute($key) !== (string) ($values[$key] ?? null));
    }

    private function inState(?int $districtId, int $stateId): bool
    {
        if ($districtId === null) {
            return false;
        }

        return District::query()->whereKey($districtId)->where('state_id', $stateId)->exists();
    }
}
