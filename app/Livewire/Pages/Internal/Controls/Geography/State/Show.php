<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\Geography\State;

use App\Domain\Shared\Geography\Models\Country;
use App\Domain\Shared\Geography\Models\State;
use App\Livewire\Concerns\CanBeDisabled;
use App\Livewire\Forms\Internal\StateForm;
use App\Livewire\Pages\Shared\Page;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;

class Show extends Page
{
    use CanBeDisabled;

    #[Locked]
    public Country $country;

    #[Locked]
    public State $state;

    public StateForm $form;

    public function mount(): void
    {
        $this->authorize('view', $this->state);

        $this->form->set($this->country, $this->state);
    }

    public function isDisabled(): bool
    {
        return $this->disabled === true || $this->cannot('update', $this->state);
    }

    public function save(): void
    {
        $this->authorize('update', $this->state);
        $this->ensureEnabled();

        $this->state = $this->form->save();
        $this->form->set($this->country, $this->state);

        $this->notifySaved((string) __('geography/states.state'), created: false);
    }

    public function render(): View
    {
        return view('livewire.pages.internal.controls.geography.state.show')->title($this->state->name);
    }
}
