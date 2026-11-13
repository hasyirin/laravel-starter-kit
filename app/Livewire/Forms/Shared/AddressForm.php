<?php

declare(strict_types=1);

namespace App\Livewire\Forms\Shared;

use App\Domain\Shared\Geography\Actions\SaveAddress;
use App\Domain\Shared\Geography\Models\Address;
use App\Domain\Shared\Geography\Models\District;
use App\Domain\Shared\Geography\Models\Postcode;
use App\Domain\Shared\Geography\Models\PostOffice;
use App\Domain\Shared\Geography\Models\State;
use App\Domain\Shared\Geography\Models\Subdistrict;
use App\Domain\Shared\Geography\Rules\LocalPostcode;
use App\Domain\Shared\Geography\Rules\LocalPostOffice;
use App\Livewire\Form;
use BackedEnum;
use Hasyirin\Address\Contracts\Addressable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Livewire\Attributes\Locked;
use LogicException;
use UnitEnum;

class AddressForm extends Form
{
    public ?string $line_1 = null;

    public ?string $line_2 = null;

    public ?string $line_3 = null;

    public ?string $postcode = null;

    public string $postcodeSearch = '';

    public ?int $post_office_id = null;

    public ?int $district_id = null;

    public ?int $subdistrict_id = null;

    public ?float $latitude = null;

    public ?float $longitude = null;

    /**
     * @var (Model&Addressable)|null
     */
    #[Locked]
    public ?Model $addressable = null;

    #[Locked]
    public string $type = 'primary';

    #[Locked]
    public bool $required = false;

    #[Locked]
    public bool $local = false;

    #[Locked]
    public bool $showingDistricts = false;

    #[Locked]
    public bool $districtsRequired = false;

    #[Locked]
    public bool $showingLocation = false;

    #[Locked]
    public bool $locationRequired = false;

    private ?PostOffice $postOffice = null;

    public function set(Model&Addressable $addressable): static
    {
        $this->addressable = $addressable;

        return $this->copy($addressable->address($this->type)->first());
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $core = $this->coreRequired();
        $required = $core ? 'required' : 'nullable';

        $rules = [
            'line_1' => [$required, 'string', 'max:255'],
            'line_2' => ['nullable', 'string', 'max:255'],
            'line_3' => ['nullable', 'string', 'max:255'],
            'postcode' => [
                'bail',
                $required,
                'string',
                Rule::exists(Postcode::class, 'code')
                    ->withoutTrashed()
                    ->when($this->post_office_id !== null, fn (Exists $rule) => $rule->where('post_office_id', $this->post_office_id)),
                Rule::when($this->local, [new LocalPostcode]),
            ],
            'post_office_id' => [
                'bail',
                $required,
                'integer',
                Rule::exists(PostOffice::class, 'id')->withoutTrashed(),
                Rule::when($this->local, [new LocalPostOffice]),
            ],
        ];

        if ($this->showingDistricts) {
            $rules = [...$rules, ...$this->districtRules($core)];
        }

        if ($this->showingLocation) {
            $rules = [...$rules, ...$this->locationRules($core)];
        }

        return $rules;
    }

    public function required(bool $required = true): static
    {
        $this->required = $required;

        return $this;
    }

    public function local(bool $local = true): static
    {
        $this->local = $local;

        throw_if($local && ! State::local() instanceof State, LogicException::class, 'Set address.locality.state in config/address.php to limit addresses to the local area.');

        return $this;
    }

    public function type(string|UnitEnum $type): static
    {
        $this->type = match (true) {
            $type instanceof BackedEnum => (string) $type->value,
            $type instanceof UnitEnum => $type->name,
            default => $type,
        };

        return $this;
    }

    public function withDistricts(bool $required = false): static
    {
        $this->showingDistricts = true;
        $this->districtsRequired = $required;

        return $this;
    }

    public function withLocation(bool $required = false): static
    {
        $this->showingLocation = true;
        $this->locationRequired = $required;

        return $this;
    }

    public function copy(?\Hasyirin\Address\Models\Address $address): static
    {
        $this->line_1 = $address?->line_1;
        $this->line_2 = $address?->line_2;
        $this->line_3 = $address?->line_3;
        $this->postcode = $address?->postcode;
        $this->post_office_id = $address?->post_office_id;
        $this->district_id = $address?->district_id;
        $this->subdistrict_id = $address?->subdistrict_id;
        $this->latitude = $address?->latitude;
        $this->longitude = $address?->longitude;

        return $this;
    }

    /**
     * @return array{line_1: ?string, line_2: ?string, line_3: ?string, postcode: ?string, post_office_id: ?int, district_id?: ?int, subdistrict_id?: ?int, latitude?: ?float, longitude?: ?float}
     */
    public function values(): array
    {
        $values = [
            'line_1' => $this->line_1,
            'line_2' => $this->line_2,
            'line_3' => $this->line_3,
            'postcode' => $this->postcode,
            'post_office_id' => $this->post_office_id,
        ];

        if ($this->showingDistricts) {
            $values = [...$values, 'district_id' => $this->district_id, 'subdistrict_id' => $this->subdistrict_id];
        }

        if ($this->showingLocation) {
            $values = [...$values, 'latitude' => $this->coordinate($this->latitude), 'longitude' => $this->coordinate($this->longitude)];
        }

        return $values;
    }

    /**
     * @return Collection<string, string>
     */
    public function postcodes(): Collection
    {
        return Postcode::options($this->postcodeSearch, current: $this->postcode, local: $this->local);
    }

    /**
     * @return Collection<int, string>
     */
    public function postOffices(): Collection
    {
        if (blank($this->postcode)) {
            return new Collection;
        }

        $query = PostOffice::query()
            ->whereHas('postcodes', fn (Builder $query) => $query->where('code', $this->postcode))
            ->orderBy('name');

        if ($this->local) {
            $query->local();
        }

        return $query->get()->mapWithKeys(fn (PostOffice $postOffice): array => [$postOffice->id => $postOffice->name]);
    }

    public function stateName(): ?string
    {
        return $this->postOffice()?->state->name;
    }

    /**
     * @return Collection<int, string>
     */
    public function districts(): Collection
    {
        $stateId = $this->stateId();

        if ($stateId === null) {
            return new Collection;
        }

        return District::query()
            ->where('state_id', $stateId)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (District $district): array => [$district->id => $district->name]);
    }

    /**
     * @return Collection<int, string>
     */
    public function subdistricts(): Collection
    {
        if ($this->district_id === null) {
            return new Collection;
        }

        return Subdistrict::query()
            ->where('district_id', $this->district_id)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Subdistrict $subdistrict): array => [$subdistrict->id => $subdistrict->name]);
    }

    public function updatedPostcode(): void
    {
        $postcode = str($this->postcode)->squish()->upper()->value();

        $this->postcode = $postcode === '' ? null : $postcode;

        $postOffices = $this->postOffices()->keys();

        if ($this->post_office_id !== null && $postOffices->contains($this->post_office_id)) {
            return;
        }

        $this->post_office_id = $postOffices->count() === 1 ? $postOffices->first() : null;

        $this->syncDistrict();
    }

    public function updatedPostOfficeId(): void
    {
        $this->syncDistrict();
    }

    public function updatedDistrictId(): void
    {
        $this->subdistrict_id = null;
    }

    private function coreRequired(): bool
    {
        return $this->required || collect($this->values())->contains(fn (mixed $value): bool => filled($value));
    }

    /**
     * @return array<string, list<mixed>>
     */
    private function districtRules(bool $core): array
    {
        $required = $this->districtsRequired && $core;
        $subdistrictRequired = $required && $this->subdistricts()->isNotEmpty();

        return [
            'district_id' => [
                $required ? 'required' : 'nullable',
                'integer',
                Rule::exists(District::class, 'id')->withoutTrashed()->where('state_id', $this->stateId()),
            ],
            'subdistrict_id' => [
                $subdistrictRequired ? 'required' : 'nullable',
                'integer',
                Rule::exists(Subdistrict::class, 'id')->withoutTrashed()->where('district_id', $this->district_id),
            ],
        ];
    }

    /**
     * @return array<string, list<mixed>>
     */
    private function locationRules(bool $core): array
    {
        $required = $this->locationRequired && $core;

        return [
            'latitude' => [
                Rule::requiredIf($required || $this->longitude !== null),
                'nullable',
                'numeric',
                'between:-90,90',
            ],
            'longitude' => [
                Rule::requiredIf($required || $this->latitude !== null),
                'nullable',
                'numeric',
                'between:-180,180',
            ],
        ];
    }

    private function coordinate(?float $value): ?float
    {
        if ($value === null) {
            return null;
        }

        return round($value, 6);
    }

    private function syncDistrict(): void
    {
        if (! $this->showingDistricts) {
            return;
        }

        $districts = $this->districts()->keys();

        if ($this->district_id !== null && $districts->doesntContain($this->district_id)) {
            $this->district_id = null;
            $this->subdistrict_id = null;
        }

        if ($this->district_id !== null) {
            return;
        }

        $this->district_id = $this->defaultDistrict($districts);
    }

    /**
     * @param  Collection<int, int>  $districts
     */
    private function defaultDistrict(Collection $districts): ?int
    {
        if ($this->local) {
            $local = District::local();

            if ($local instanceof District && $districts->contains($local->id)) {
                return $local->id;
            }
        }

        if ($districts->count() === 1) {
            return $districts->first();
        }

        return null;
    }

    private function stateId(): ?int
    {
        return $this->postOffice()?->state_id;
    }

    private function postOffice(): ?PostOffice
    {
        if ($this->post_office_id === null) {
            return null;
        }

        if ($this->postOffice?->id === $this->post_office_id) {
            return $this->postOffice;
        }

        $this->postOffice = PostOffice::query()->with('state')->find($this->post_office_id);

        return $this->postOffice;
    }

    public function save((Model&Addressable)|null $addressable = null): ?Address
    {
        $this->validate();

        $model = $addressable ?? $this->addressable;

        throw_if(! $model instanceof Model, LogicException::class, 'Pass the model of the address to set() or save().');

        return SaveAddress::make()->execute($model, $this->values(), $this->type);
    }
}
