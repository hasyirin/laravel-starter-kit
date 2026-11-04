<?php

declare(strict_types=1);

namespace App\Livewire\DataTables\Admin\Role;

use App\Domain\Shared\Permission\Models\Role;
use App\Domain\User\Models\User;
use App\Filament\Actions\ViewAction;
use App\Filament\Tables\Columns\TimestampColumn;
use App\Livewire\DataTables\DataTable;
use Filament\Actions\Action;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Defer;
use Livewire\Attributes\Locked;

#[Defer]
class UserDataTable extends DataTable
{
    #[Locked]
    public int $roleId;

    public function mount(): void
    {
        $this->authorize('view', $this->role);
        $this->authorize('viewAny', User::class);
    }

    #[Computed]
    public function role(): Role
    {
        return Role::query()->findOrFail($this->roleId);
    }

    /**
     * @return Builder<User>
     */
    protected function query(): Builder
    {
        return User::query()->role($this->role);
    }

    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        return [
            TextColumn::make('name')->label((string) __('common/inputs.name'))->searchable()->sortable(),
            TextColumn::make('email')->label((string) __('common/inputs.email'))->searchable()->sortable(),
            TimestampColumn::make('created_at'),
        ];
    }

    /**
     * @return array<int, Action>
     */
    protected function actions(): array
    {
        return [
            ViewAction::make()
                ->visible(fn (User $user): bool => $this->can('view', $user))
                ->action(fn (User $user) => $this->redirect(route('admin.controls.roles.users.show', [$this->role, $user]), navigate: true)),
        ];
    }
}
