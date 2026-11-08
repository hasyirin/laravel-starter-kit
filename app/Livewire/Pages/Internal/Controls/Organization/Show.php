<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\Organization;

use App\Domain\Shared\Organization\Models\Organization;
use App\Livewire\Concerns\CanBeDisabled;
use App\Livewire\Forms\Internal\OrganizationForm;
use App\Livewire\Pages\Shared\Page;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;

class Show extends Page
{
    use CanBeDisabled;

    #[Locked]
    public Organization $organization;

    public OrganizationForm $form;

    public function mount(): void
    {
        $this->authorize('view', $this->organization);

        $this->form->set($this->organization);
    }

    public function isDisabled(?string $part = null): bool
    {
        return $this->disabled === true || $this->cannot('update', $this->organization);
    }

    public function save(): void
    {
        $this->authorize('update', $this->organization);
        $this->ensureEnabled();

        $this->organization = $this->form->save();
        $this->form->set($this->organization);

        $this->notifySaved((string) __('organization/organizations.organization'), created: false);
    }

    public function render(): View
    {
        return view('livewire.pages.internal.controls.organization.show')->title($this->organization->name);
    }
}
