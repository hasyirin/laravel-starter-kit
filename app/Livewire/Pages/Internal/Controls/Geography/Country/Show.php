<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\Geography\Country;

use App\Domain\Shared\Geography\Models\Country;
use App\Livewire\Concerns\CanBeDisabled;
use App\Livewire\Forms\Internal\CountryForm;
use App\Livewire\Pages\Shared\Page;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;

class Show extends Page
{
    use CanBeDisabled;

    #[Locked]
    public Country $country;

    public CountryForm $form;

    public function mount(): void
    {
        $this->authorize('view', $this->country);

        $this->form->set($this->country);
    }

    public function isDisabled(): bool
    {
        return $this->disabled === true || $this->cannot('update', $this->country);
    }

    public function save(): void
    {
        $this->authorize('update', $this->country);
        $this->ensureEnabled();

        $this->country = $this->form->save();
        $this->form->set($this->country);

        $this->notifySaved((string) __('geography/countries.country'), created: false);
    }

    public function render(): View
    {
        return view('livewire.pages.internal.controls.geography.country.show')->title($this->country->name);
    }
}
