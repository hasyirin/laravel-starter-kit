<?php

declare(strict_types=1);

namespace App\Livewire\Forms\Internal;

use App\Domain\Shared\Geography\Actions\SaveDistrict;
use App\Domain\Shared\Geography\Models\District;
use App\Domain\Shared\Geography\Models\State;
use App\Livewire\Form;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Livewire\Attributes\Locked;

class DistrictForm extends Form
{
    #[Locked]
    public State $state;

    #[Locked]
    public ?District $district = null;

    public string $code = '';

    public string $name = '';

    public function set(State $state, ?District $district = null): static
    {
        $this->state = $state;
        $this->district = $district;
        $this->code = $district->code ?? '';
        $this->name = $district->name ?? '';

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
                'max:255',
                Rule::unique(District::class, 'code')
                    ->where('state_id', $this->state->id)
                    ->ignore($this->district?->id)
                    ->unless($this->district instanceof District, fn (Unique $rule): Unique => $rule->withoutTrashed()),
            ],
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    public function editable(string $part): bool
    {
        if ($part !== 'code' || ! $this->district instanceof District) {
            return true;
        }

        return ! $this->district->holdsLocality();
    }

    public function save(): District
    {
        $this->code = str($this->code)->squish()->value();
        $this->name = str($this->name)->squish()->value();

        if ($this->district instanceof District && ! $this->editable('code')) {
            $this->code = $this->district->code;
        }

        $this->validate();

        return SaveDistrict::make()->execute($this->state, $this->district ?? new District, $this->code, $this->name);
    }
}
