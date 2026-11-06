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
     * @param  array{line_1?: ?string, line_2?: ?string, line_3?: ?string, postcode?: ?string, post_office_id?: ?int, district_id?: ?int, subdistrict_id?: ?int}  $values
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

        $districtId = array_key_exists('district_id', $values) ? $values['district_id'] : $saved?->district_id;

        if (! $this->inState($districtId, $postOffice->state_id)) {
            $attributes = [...$attributes, 'district_id' => null, 'subdistrict_id' => null];
        }

        $address = $addressable->address($type)->updateOrCreate([], $attributes);

        assert($address instanceof Address);

        return $address;
    }

    private function inState(?int $districtId, int $stateId): bool
    {
        if ($districtId === null) {
            return false;
        }

        return District::query()->whereKey($districtId)->where('state_id', $stateId)->exists();
    }
}
