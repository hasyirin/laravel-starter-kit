<?php

declare(strict_types=1);

namespace App\Livewire\Forms\Internal;

use App\Domain\Shared\Geography\Actions\SavePostOffice;
use App\Domain\Shared\Geography\Models\PostOffice;
use App\Domain\Shared\Geography\Models\State;
use App\Livewire\Form;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Livewire\Attributes\Locked;

class PostOfficeForm extends Form
{
    #[Locked]
    public State $state;

    #[Locked]
    public ?PostOffice $postOffice = null;

    public string $name = '';

    public function set(State $state, ?PostOffice $postOffice = null): static
    {
        $this->state = $state;
        $this->postOffice = $postOffice;
        $this->name = $postOffice->name ?? '';

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(PostOffice::class, 'name')
                    ->where('state_id', $this->state->id)
                    ->ignore($this->postOffice?->id)
                    ->unless($this->postOffice instanceof PostOffice, fn (Unique $rule): Unique => $rule->withoutTrashed()),
            ],
        ];
    }

    public function save(): PostOffice
    {
        $this->name = str($this->name)->squish()->value();

        $this->validate();

        return SavePostOffice::make()->execute($this->state, $this->postOffice ?? new PostOffice, $this->name);
    }
}
