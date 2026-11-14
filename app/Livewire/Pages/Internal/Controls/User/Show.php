<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\User;

use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Permission\Models\Role;
use App\Domain\User\Actions\Management\VerifyUserEmail;
use App\Domain\User\Actions\Management\VerifyUserPhone;
use App\Domain\User\Models\User;
use App\Livewire\Concerns\CanBeDisabled;
use App\Livewire\Pages\Internal\Controls\User\Concerns\InteractsWithUserForm;
use App\Livewire\Pages\Shared\Page;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use SensitiveParameter;

class Show extends Page
{
    use CanBeDisabled;
    use InteractsWithUserForm;

    #[Locked]
    public User $user;

    #[Locked]
    public ?Role $role = null;

    #[Locked]
    public ?Organization $organization = null;

    #[Locked]
    public ?Department $department = null;

    #[Locked]
    public ?Branch $branch = null;

    public function mount(): void
    {
        $this->authorize('view', $this->user);

        $parent = $this->parent();

        if ($parent instanceof Department || $parent instanceof Branch) {
            abort_unless($parent->organization_id === $this->organization?->id, 404);
        }

        if ($parent !== null) {
            $this->authorize('view', $parent);
        }

        $this->form->set($this->user);
    }

    public static function url(User $user, Role|Organization|Department|Branch|null $parent = null): string
    {
        return match (true) {
            $parent instanceof Role => route('internal.controls.roles.users.show', [$parent, $user]),
            $parent instanceof Organization => route('internal.controls.organizations.users.show', [$parent, $user]),
            $parent instanceof Department => route('internal.controls.organizations.departments.users.show', [$parent->organization_id, $parent, $user]),
            $parent instanceof Branch => route('internal.controls.organizations.branches.users.show', [$parent->organization_id, $parent, $user]),
            default => route('internal.controls.users.show', $user),
        };
    }

    public function parent(): Role|Organization|Department|Branch|null
    {
        return $this->role ?? $this->department ?? $this->branch ?? $this->organization;
    }

    /**
     * @return list<array{label: string, href?: string}>
     */
    #[Computed]
    public function breadcrumbs(): array
    {
        return [...$this->parentBreadcrumbs(), ['label' => $this->user->email]];
    }

    #[Computed]
    public function backUrl(): string
    {
        /** @var array{label: string, href: string} $crumb */
        $crumb = collect($this->parentBreadcrumbs())->last();

        return $crumb['href'];
    }

    public function isDisabled(): bool
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

    public function verifyEmail(mixed $email = null): void
    {
        $this->authorize('update', $this->user);

        /** @var User $actor */
        $actor = auth()->user();

        $this->dispatch('modal-close', name: 'verify-email');

        if (! is_string($email) || ! VerifyUserEmail::make()->execute($actor, $this->user, $email)) {
            Flux::toast(variant: 'warning', text: __('user/users.verify.changed'));
        }
    }

    public function verifyPhone(#[SensitiveParameter] mixed $phone = null): void
    {
        $this->authorize('update', $this->user);

        /** @var User $actor */
        $actor = auth()->user();

        $this->dispatch('modal-close', name: 'verify-phone');

        if (! is_string($phone) || ! VerifyUserPhone::make()->execute($actor, $this->user, $phone)) {
            Flux::toast(variant: 'warning', text: __('user/users.verify_phone.changed'));
        }
    }

    /**
     * @return list<array{label: string, href: string}>
     */
    private function parentBreadcrumbs(): array
    {
        if ($this->role instanceof Role) {
            return [
                ['label' => (string) __('permission/roles.title'), 'href' => route('internal.controls.roles.index')],
                ['label' => $this->role->label, 'href' => route('internal.controls.roles.show', $this->role)],
                ['label' => (string) __('permission/roles.tabs.users'), 'href' => route('internal.controls.roles.show', ['role' => $this->role, 'tab' => 'users'])],
            ];
        }

        if (! $this->organization instanceof Organization) {
            return [['label' => (string) __('user/users.title'), 'href' => route('internal.controls.users.index')]];
        }

        $crumbs = [
            ['label' => (string) __('organization/organizations.title'), 'href' => route('internal.controls.organizations.index')],
            ['label' => $this->organization->name, 'href' => route('internal.controls.organizations.show', $this->organization)],
        ];

        if ($this->department instanceof Department) {
            return [
                ...$crumbs,
                ['label' => (string) __('organization/organizations.tabs.departments'), 'href' => route('internal.controls.organizations.show', ['organization' => $this->organization, 'tab' => 'departments'])],
                ['label' => $this->department->name, 'href' => route('internal.controls.organizations.departments.show', [$this->organization, $this->department])],
                ['label' => (string) __('organization/departments.tabs.users'), 'href' => route('internal.controls.organizations.departments.show', ['organization' => $this->organization, 'department' => $this->department, 'tab' => 'users'])],
            ];
        }

        if ($this->branch instanceof Branch) {
            return [
                ...$crumbs,
                ['label' => (string) __('organization/organizations.tabs.branches'), 'href' => route('internal.controls.organizations.show', ['organization' => $this->organization, 'tab' => 'branches'])],
                ['label' => $this->branch->name, 'href' => route('internal.controls.organizations.branches.show', [$this->organization, $this->branch])],
                ['label' => (string) __('organization/branches.tabs.users'), 'href' => route('internal.controls.organizations.branches.show', ['organization' => $this->organization, 'branch' => $this->branch, 'tab' => 'users'])],
            ];
        }

        return [
            ...$crumbs,
            ['label' => (string) __('organization/organizations.tabs.users'), 'href' => route('internal.controls.organizations.show', ['organization' => $this->organization, 'tab' => 'users'])],
        ];
    }

    public function render(): View
    {
        return view('livewire.pages.internal.controls.user.show')->title($this->user->name);
    }
}
