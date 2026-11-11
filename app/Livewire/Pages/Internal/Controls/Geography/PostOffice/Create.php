<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\Geography\PostOffice;

use App\Domain\Shared\Geography\Models\Country;
use App\Domain\Shared\Geography\Models\PostOffice;
use App\Domain\Shared\Geography\Models\State;
use App\Livewire\Forms\Internal\PostOfficeForm;
use App\Livewire\Pages\Shared\Page;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;

class Create extends Page
{
    #[Locked]
    public Country $country;

    #[Locked]
    public State $state;

    public PostOfficeForm $form;

    public function mount(): void
    {
        $this->authorize('view', $this->state);
        $this->authorize('create', PostOffice::class);

        $this->form->set($this->state);
    }

    public function save(): void
    {
        $this->authorize('create', PostOffice::class);

        $postOffice = $this->form->save();

        $this->notifySaved((string) __('geography/cities.city'), created: true);
        $this->redirect(route('internal.controls.geography.countries.states.cities.show', [$this->country, $this->state, $postOffice]), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.pages.internal.controls.geography.post-office.create')->title(__('geography/cities.create.title'));
    }
}
