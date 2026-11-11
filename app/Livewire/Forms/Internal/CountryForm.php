<?php

declare(strict_types=1);

namespace App\Livewire\Forms\Internal;

use App\Domain\Shared\Geography\Actions\SaveCountry;
use App\Domain\Shared\Geography\Models\Country;
use App\Livewire\Form;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Livewire\Attributes\Locked;

class CountryForm extends Form
{
    #[Locked]
    public ?Country $country = null;

    public string $code = '';

    public string $name = '';

    public ?string $alpha_2 = null;

    public function set(Country $country): static
    {
        $this->country = $country;
        $this->code = $country->code;
        $this->name = $country->name;
        $this->alpha_2 = $country->alpha_2;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:3',
                Rule::unique(Country::class, 'code')
                    ->ignore($this->country?->id)
                    ->unless($this->country instanceof Country, fn (Unique $rule): Unique => $rule->withoutTrashed()),
            ],
            'name' => ['required', 'string', 'max:255'],
            'alpha_2' => ['nullable', 'string', 'size:2'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return [
            'alpha_2' => (string) __('common/inputs.alpha_2'),
        ];
    }

    public function editable(string $part): bool
    {
        if ($part !== 'code' || ! $this->country instanceof Country) {
            return true;
        }

        return ! $this->country->holdsLocality();
    }

    public function save(): Country
    {
        $this->code = str($this->code)->squish()->value();
        $this->name = str($this->name)->squish()->value();
        $this->alpha_2 = str($this->alpha_2)->squish()->value();

        if ($this->country instanceof Country && ! $this->editable('code')) {
            $this->code = $this->country->code;
        }

        $this->validate();

        return SaveCountry::make()->execute($this->country ?? new Country, $this->code, $this->name, blank($this->alpha_2) ? null : $this->alpha_2);
    }
}
