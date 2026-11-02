<?php

declare(strict_types=1);

namespace App\Livewire\DataTables\Admin\User;

use App\Domain\Shared\Permission\Models\Role;
use App\Domain\User\Actions\ChangeUserProfile;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use App\Filament\Actions\CreateAction;
use App\Livewire\DataTables\DataTable;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Translation\PotentiallyTranslatedString;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Defer;
use Livewire\Attributes\Locked;

#[Defer]
class RoleDataTable extends DataTable
{
    #[Locked]
    public int $userId;

    public function mount(): void
    {
        $this->authorize('view', $this->user);
    }

    /**
     * @return Builder<Role>
     */
    protected function query(): Builder
    {
        return Role::query()->with('permissions')->whereIn('name', $this->currentRoleNames());
    }

    /**
     * @return array<int, Action>
     */
    protected function headerActions(): array
    {
        return [
            CreateAction::make('attach')
                ->label((string) __('user/users.roles_tab.attach'))
                ->modalHeading((string) __('user/users.roles_tab.attach'))
                ->modalSubmitActionLabel((string) __('user/users.roles_tab.attach'))
                ->visible(fn (): bool => $this->can('update', [$this->user, 'roles']))
                ->schema([
                    Select::make('roles')
                        ->label((string) __('user/users.roles'))
                        ->multiple()
                        ->required()
                        ->options(fn (): array => $this->attachableRoles()->mapWithKeys(fn (Role $role): array => [$role->name => $role->label])->all())
                        ->rules([fn (): Closure => $this->mustBeAttachable(...)]),
                ])
                ->action(function (array $data, ChangeUserProfile $changeUserProfile): void {
                    $this->authorize('update', [$this->user, 'roles']);

                    /** @var list<string> $selected */
                    $selected = $data['roles'];

                    $this->changeRoles($changeUserProfile, [...$this->currentRoleNames(), ...$selected]);
                }),
        ];
    }

    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        return [
            TextColumn::make('label')->label((string) __('user/users.roles_tab.role')),
            TextColumn::make('is_system')
                ->label((string) __('user/users.roles_tab.system'))
                ->badge()
                ->formatStateUsing(fn (bool $state): ?string => $state ? (string) __('user/users.roles_tab.system') : null),
        ];
    }

    /**
     * @return array<int, Action>
     */
    protected function actions(): array
    {
        return [
            Action::make('detach')
                ->label((string) __('user/users.roles_tab.detach'))
                ->icon(Heroicon::Minus)
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading((string) __('user/users.roles_tab.detach_heading'))
                ->modalSubmitActionLabel((string) __('user/users.roles_tab.detach'))
                ->visible(fn (Role $role): bool => $this->canDetach($role))
                ->action(function (Role $role, ChangeUserProfile $changeUserProfile): void {
                    $this->authorize('update', [$this->user, 'roles']);

                    $this->changeRoles($changeUserProfile, array_values(array_diff($this->currentRoleNames(), [$role->name])));
                }),
        ];
    }

    #[Computed]
    public function user(): User
    {
        return User::query()->findOrFail($this->userId);
    }

    private function actor(): User
    {
        /** @var User $actor */
        $actor = auth()->user();

        return $actor;
    }

    private function actorHasBypass(): bool
    {
        return $this->actor()->checkPermissionTo(config()->string('system.permissions.bypass-authorization'));
    }

    private function canDetach(Role $role): bool
    {
        if ($this->cannot('update', [$this->user, 'roles'])) {
            return false;
        }

        if (! $role->carries_bypass) {
            return true;
        }

        return $this->actorHasBypass() && ! $this->actor()->is($this->user);
    }

    /**
     * @return list<string>
     */
    private function currentRoleNames(): array
    {
        $profile = $this->user->profile;

        if (! $profile instanceof Profile) {
            return [];
        }

        return array_values($profile->getRoleNames()->map(fn (mixed $name): string => (string) $name)->all());
    }

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    private function mustBeAttachable(string $attribute, mixed $value, Closure $fail): void
    {
        $allowed = $this->attachableRoles()->map(fn (Role $role): string => $role->name);

        if (collect(is_array($value) ? $value : [])->diff($allowed)->isNotEmpty()) {
            $fail('validation.in')->translate();
        }
    }

    /**
     * @return Collection<int, Role>
     */
    private function attachableRoles(): Collection
    {
        return Role::query()
            ->with('permissions')
            ->whereNotIn('name', $this->currentRoleNames())
            ->orderBy('name')
            ->get()
            ->filter(fn (Role $role): bool => $this->actorHasBypass() || ! $role->carries_bypass);
    }

    /**
     * @param  list<string>  $roles
     */
    private function changeRoles(ChangeUserProfile $changeUserProfile, array $roles): void
    {
        $changeUserProfile->execute($this->actor(), $this->user, roles: $roles);

        Flux::toast(variant: 'success', text: __('user/users.roles_tab.saved'));
    }
}
