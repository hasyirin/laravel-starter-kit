<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\Geography\District;

use App\Domain\Shared\Geography\Models\Country;
use App\Domain\Shared\Geography\Models\District;
use App\Domain\Shared\Geography\Models\State;
use App\Livewire\Concerns\CanBeDisabled;
use App\Livewire\Forms\Internal\DistrictForm;
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

    #[Locked]
    public District $district;

    public DistrictForm $form;

    public function mount(): void
    {
        $this->authorize('view', $this->district);

        $this->form->set($this->state, $this->district);
    }

    public function isDisabled(): bool
    {
        return $this->disabled === true || $this->cannot('update', $this->district);
    }

    public function save(): void
    {
        $this->authorize('update', $this->district);
        $this->ensureEnabled();

        $this->district = $this->form->save();
        $this->form->set($this->state, $this->district);

        $this->notifySaved((string) __('geography/districts.district'), created: false);
    }

    public function render(): View
    {
        return view('livewire.pages.internal.controls.geography.district.show')->title($this->district->name);
    }
}
