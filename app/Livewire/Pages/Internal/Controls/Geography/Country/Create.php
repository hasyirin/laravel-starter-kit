<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\Geography\Country;

use App\Domain\Shared\Geography\Models\Country;
use App\Livewire\Forms\Internal\CountryForm;
use App\Livewire\Pages\Shared\Page;
use Illuminate\Contracts\View\View;

class Create extends Page
{
    public CountryForm $form;

    public function mount(): void
    {
        $this->authorize('create', Country::class);
    }

    public function save(): void
    {
        $this->authorize('create', Country::class);

        $country = $this->form->save();

        $this->notifySaved((string) __('geography/countries.country'), created: true);
        $this->redirect(route('internal.controls.geography.countries.show', $country), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.pages.internal.controls.geography.country.create')->title(__('geography/countries.create.title'));
    }
}
