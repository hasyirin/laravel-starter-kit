<?php

declare(strict_types=1);

namespace App\Livewire\Forms\Internal;

use App\Domain\Shared\Geography\Actions\SaveState;
use App\Domain\Shared\Geography\Models\Country;
use App\Domain\Shared\Geography\Models\State;
use App\Livewire\Form;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Livewire\Attributes\Locked;

class StateForm extends Form
{
    #[Locked]
    public Country $country;

    #[Locked]
    public ?State $state = null;

    public string $code = '';

    public string $name = '';

    public function set(Country $country, ?State $state = null): static
    {
        $this->country = $country;
        $this->state = $state;
        $this->code = $state->code ?? '';
        $this->name = $state->name ?? '';

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
                Rule::unique(State::class, 'code')
                    ->where('country_id', $this->country->id)
                    ->ignore($this->state?->id)
                    ->unless($this->state instanceof State, fn (Unique $rule): Unique => $rule->withoutTrashed()),
            ],
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    public function editable(string $part): bool
    {
        if ($part !== 'code' || ! $this->state instanceof State) {
            return true;
        }

        return ! $this->state->holdsLocality();
    }

    public function save(): State
    {
        $this->code = str($this->code)->squish()->value();
        $this->name = str($this->name)->squish()->value();

        if ($this->state instanceof State && ! $this->editable('code')) {
            $this->code = $this->state->code;
        }

        $this->validate();

        return SaveState::make()->execute($this->country, $this->state ?? new State, $this->code, $this->name);
    }
}
