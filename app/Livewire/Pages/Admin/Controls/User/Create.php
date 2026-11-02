<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Admin\Controls\User;

use App\Domain\User\Actions\CreateUser;
use App\Domain\User\Models\User;
use App\Livewire\Pages\Admin\Controls\User\Concerns\InteractsWithUserForm;
use App\Livewire\Pages\Shared\Page;
use Illuminate\Contracts\View\View;

class Create extends Page
{
    use InteractsWithUserForm;

    public function mount(): void
    {
        $this->authorize('create', User::class);
    }

    public function save(CreateUser $createUser): void
    {
        $this->authorize('create', User::class);

        $this->form->email = str($this->form->email)->lower()->value();
        $this->form->validate();

        /** @var User $actor */
        $actor = auth()->user();

        $user = $createUser->execute($actor, $this->form->name, $this->form->email, $this->form->password, $this->form->placement());

        $this->redirect(route('admin.controls.users.show', $user), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.pages.admin.controls.user.create')->title(__('user/users.create.title'));
    }
}
