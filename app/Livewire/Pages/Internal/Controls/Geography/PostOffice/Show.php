<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\Geography\PostOffice;

use App\Domain\Shared\Geography\Models\Country;
use App\Domain\Shared\Geography\Models\PostOffice;
use App\Domain\Shared\Geography\Models\State;
use App\Livewire\Concerns\CanBeDisabled;
use App\Livewire\Forms\Internal\PostOfficeForm;
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
    public PostOffice $postOffice;

    public PostOfficeForm $form;

    public function mount(): void
    {
        $this->authorize('view', $this->postOffice);

        $this->form->set($this->state, $this->postOffice);
    }

    public function isDisabled(): bool
    {
        return $this->disabled === true || $this->cannot('update', $this->postOffice);
    }

    public function save(): void
    {
        $this->authorize('update', $this->postOffice);
        $this->ensureEnabled();

        $this->postOffice = $this->form->save();
        $this->form->set($this->state, $this->postOffice);

        $this->notifySaved((string) __('geography/cities.city'), created: false);
    }

    public function render(): View
    {
        return view('livewire.pages.internal.controls.geography.post-office.show')->title($this->postOffice->name);
    }
}
