<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\User;

use App\Domain\Shared\Permission\Models\Role;
use App\Domain\User\Actions\VerifyUserEmail;
use App\Domain\User\Models\User;
use App\Livewire\Concerns\CanBeDisabled;
use App\Livewire\Pages\Internal\Controls\User\Concerns\InteractsWithUserForm;
use App\Livewire\Pages\Shared\Page;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;

class Show extends Page
{
    use CanBeDisabled;
    use InteractsWithUserForm;

    #[Locked]
    public User $user;

    #[Locked]
    public ?Role $role = null;

    public function mount(): void
    {
        $this->authorize('view', $this->user);

        if ($this->role instanceof Role) {
            $this->authorize('view', $this->role);
        }

        $this->form->set($this->user);
    }

    public function isDisabled(?string $part = null): bool
    {
        return $this->disabled === true || $this->cannot('update', $this->user);
    }

    public function editable(string $part): bool
    {
        return ! $this->isDisabled() && $this->form->editable($part);
    }

    #[Computed]
    public function subheading(): string
    {
        $profile = $this->user->profile;

        $placement = collect([
            $profile?->position?->name,
            $profile?->department?->name,
            $profile?->organization?->name,
        ])->filter();

        if ($placement->isEmpty()) {
            return $this->user->email;
        }

        return $placement->implode(' · ');
    }

    public function save(): void
    {
        $this->authorize('update', $this->user);

        $this->user = $this->form->save();
        $this->form->set($this->user);

        $this->notifySaved((string) __('user/users.user'), created: false);
    }

    public function verifyEmail(): void
    {
        $this->authorize('update', $this->user);

        /** @var User $actor */
        $actor = auth()->user();

        VerifyUserEmail::make()->execute($actor, $this->user);

        $this->dispatch('modal-close', name: 'verify-email');
    }

    public function render(): View
    {
        return view('livewire.pages.internal.controls.user.show')->title($this->user->name);
    }
}
