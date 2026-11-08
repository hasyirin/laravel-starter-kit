<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\Organization;

use App\Domain\Shared\Organization\Models\Organization;
use App\Livewire\Forms\Internal\OrganizationForm;
use App\Livewire\Pages\Shared\Page;
use Illuminate\Contracts\View\View;

class Create extends Page
{
    public OrganizationForm $form;

    public function mount(): void
    {
        $this->authorize('create', Organization::class);
    }

    public function save(): void
    {
        $this->authorize('create', Organization::class);

        $organization = $this->form->save();

        $this->notifySaved((string) __('organization/organizations.organization'), created: true);
        $this->redirect(route('internal.controls.organizations.show', $organization), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.pages.internal.controls.organization.create')->title(__('organization/organizations.create.title'));
    }
}
