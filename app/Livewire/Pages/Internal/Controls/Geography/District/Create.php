<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\Geography\District;

use App\Domain\Shared\Geography\Models\Country;
use App\Domain\Shared\Geography\Models\District;
use App\Domain\Shared\Geography\Models\State;
use App\Livewire\Forms\Internal\DistrictForm;
use App\Livewire\Pages\Shared\Page;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;

class Create extends Page
{
    #[Locked]
    public Country $country;

    #[Locked]
    public State $state;

    public DistrictForm $form;

    public function mount(): void
    {
        $this->authorize('view', $this->state);
        $this->authorize('create', District::class);

        $this->form->set($this->state);
    }

    public function save(): void
    {
        $this->authorize('create', District::class);

        $district = $this->form->save();

        $this->notifySaved((string) __('geography/districts.district'), created: true);
        $this->redirect(route('internal.controls.geography.countries.states.districts.show', [$this->country, $this->state, $district]), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.pages.internal.controls.geography.district.create')->title(__('geography/districts.create.title'));
    }
}
