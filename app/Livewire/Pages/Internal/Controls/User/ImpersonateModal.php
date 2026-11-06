<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\User;

use App\Domain\Shared\Permission\Models\Role;
use App\Domain\User\Actions\StartImpersonation;
use App\Domain\User\Concerns\PasswordValidationRules;
use App\Domain\User\Models\User;
use App\Domain\User\Support\Impersonation;
use App\Http\Responses\LoginResponse;
use App\Livewire\Modal;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;

class ImpersonateModal extends Modal
{
    use PasswordValidationRules;

    #[Locked]
    public ?Role $role = null;

    #[Locked]
    public bool $startsOnIndexPage = false;

    #[Locked]
    public ?User $user = null;

    public string $password = '';

    #[On('show')]
    public function show(?int $id = null): void
    {
        $this->user = User::query()->findOrFail($id);

        $this->authorizeImpersonation();

        $this->reset('password');

        parent::show();
    }

    public function impersonate(): void
    {
        $user = $this->authorizeImpersonation();

        $this->validate(['password' => $this->currentPasswordRules()]);

        /** @var User $impersonator */
        $impersonator = auth()->user();

        $startUrl = $this->startUrl($user);
        $landingUrl = $this->opensStartPage($user) ? $startUrl : LoginResponse::home($user);

        StartImpersonation::make()->execute($impersonator, $user, $startUrl);

        $this->redirect($landingUrl);
    }

    private function authorizeImpersonation(): User
    {
        /** @var User $impersonator */
        $impersonator = auth()->user();

        abort_unless($this->user instanceof User && Impersonation::allows($impersonator, $this->user), 403);

        return $this->user;
    }

    private function startUrl(User $user): string
    {
        if ($this->startsOnIndexPage) {
            return route('internal.controls.users.index');
        }

        if ($this->role instanceof Role) {
            return route('internal.controls.roles.users.show', [$this->role, $user]);
        }

        return route('internal.controls.users.show', $user);
    }

    private function opensStartPage(User $user): bool
    {
        if (! $user->can(config()->string('system.permissions.internal.access'))) {
            return false;
        }

        if ($this->startsOnIndexPage) {
            return $user->can('viewAny', User::class);
        }

        if ($this->role instanceof Role && $user->cannot('view', $this->role)) {
            return false;
        }

        return $user->can('view', $user);
    }

    public function render(): View
    {
        return view('livewire.pages.internal.controls.user.impersonate-modal');
    }
}
