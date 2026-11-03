<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Admin\Controls\User;

use App\Domain\User\Actions\VerifyUserEmail;
use App\Domain\User\Models\User;
use App\Livewire\Concerns\CanBeDisabled;
use App\Livewire\Pages\Admin\Controls\User\Concerns\InteractsWithUserForm;
use App\Livewire\Pages\Shared\Page;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;

class Show extends Page
{
    use CanBeDisabled;
    use InteractsWithUserForm;

    public User $user;

    public function mount(User $user): void
    {
        $this->authorize('view', $user);

        $this->user = $user;
        $this->form->set($user);
    }

    public function isDisabled(?string $part = null): bool
    {
        return $this->disabled === true || $this->cannot('update', $this->user);
    }

    public function canChange(string $type): bool
    {
        return ! $this->isDisabled() && $this->form->canChange($type);
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
