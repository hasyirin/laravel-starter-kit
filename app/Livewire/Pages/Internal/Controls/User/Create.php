<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\User;

use App\Domain\User\Models\User;
use App\Livewire\Pages\Internal\Controls\User\Concerns\InteractsWithUserForm;
use App\Livewire\Pages\Shared\Page;
use Illuminate\Contracts\View\View;

class Create extends Page
{
    use InteractsWithUserForm;

    public function mount(): void
    {
        $this->authorize('create', User::class);
    }

    public function save(): void
    {
        $this->authorize('create', User::class);

        $user = $this->form->save();

        $this->notifySaved((string) __('user/users.user'), created: true);
        $this->redirect(route('internal.controls.users.show', $user), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.pages.internal.controls.user.create')->title(__('user/users.create.title'));
    }
}
