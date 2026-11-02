<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Admin\Controls\User;

use App\Domain\User\Actions\UpdateUser;
use App\Domain\User\Actions\VerifyUserEmail;
use App\Domain\User\Models\User;
use App\Livewire\Concerns\CanBeDisabled;
use App\Livewire\Pages\Admin\Controls\User\Concerns\InteractsWithUserForm;
use App\Livewire\Pages\Shared\Page;
use Flux\Flux;
use Illuminate\Contracts\View\View;

class Show extends Page
{
    use CanBeDisabled;
    use InteractsWithUserForm;

    public User $user;

    public function mount(User $user): void
    {
        $this->authorize('view', $user);

        $this->user = $user;
        $this->form->fillFromUser($user);
    }

    public function isDisabled(?string $part = null): bool
    {
        return $this->disabled === true || $this->cannot('update', $this->user);
    }

    public function save(UpdateUser $updateUser): void
    {
        $this->authorize('update', $this->user);

        $this->form->email = str($this->form->email)->lower()->value();
        $this->form->validate();

        /** @var User $actor */
        $actor = auth()->user();

        $updateUser->execute($actor, $this->user, $this->form->name, $this->form->email, $this->form->passwordOrNull(), $this->form->placement());

        $this->form->reset('password', 'password_confirmation');
        $this->user->refresh();

        Flux::toast(variant: 'success', text: __('user/users.updated'));
    }

    public function verifyEmail(VerifyUserEmail $verifyUserEmail): void
    {
        $this->authorize('update', $this->user);

        /** @var User $actor */
        $actor = auth()->user();

        $verifyUserEmail->execute($actor, $this->user);

        $this->dispatch('modal-close', name: 'verify-email');
    }

    public function render(): View
    {
        return view('livewire.pages.admin.controls.user.show')->title($this->user->name);
    }
}
