<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\Geography\State;

use App\Domain\Shared\Geography\Models\Country;
use App\Domain\Shared\Geography\Models\State;
use App\Livewire\Forms\Internal\StateForm;
use App\Livewire\Pages\Shared\Page;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;

class Create extends Page
{
    #[Locked]
    public Country $country;

    public StateForm $form;

    public function mount(): void
    {
        $this->authorize('view', $this->country);
        $this->authorize('create', State::class);

        $this->form->set($this->country);
    }

    public function save(): void
    {
        $this->authorize('create', State::class);

        $state = $this->form->save();

        $this->notifySaved((string) __('geography/states.state'), created: true);
        $this->redirect(route('internal.controls.geography.countries.states.show', [$this->country, $state]), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.pages.internal.controls.geography.state.create')->title(__('geography/states.create.title'));
    }
}
