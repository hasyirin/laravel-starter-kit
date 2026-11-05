<?php

declare(strict_types=1);

namespace App\Livewire\DataTables\Internal;

use App\Domain\Shared\Permission\Actions\DeleteRole;
use App\Domain\Shared\Permission\Models\Role;
use App\Filament\Actions\CreateAction;
use App\Filament\Actions\DeleteAction;
use App\Filament\Actions\ViewAction;
use App\Filament\Tables\Columns\TimestampColumn;
use App\Livewire\DataTables\DataTable;
use Filament\Actions\Action;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Defer;

#[Defer]
class RoleDataTable extends DataTable
{
    public function mount(): void
    {
        $this->authorize('viewAny', Role::class);
    }

    /**
     * @return Builder<Role>
     */
    protected function query(): Builder
    {
        return Role::query();
    }

    /**
     * @return array<int, Action>
     */
    protected function headerActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn (): bool => $this->can('create', Role::class))
                ->action($this->to(...)),
        ];
    }

    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        return [
            TextColumn::make('name')
                ->label((string) __('common/inputs.name'))
                ->state(fn (Role $role): string => $role->label)
                ->searchable()
                ->sortable(),
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
                ->visible(fn (Role $role): bool => $this->can('view', $role))
                ->action($this->to(...)),

            DeleteAction::make()
                ->through(DeleteRole::class)
                ->visible(fn (Role $role): bool => $this->can('delete', $role) && ! $role->is_system && ! $role->assignedToProfiles()),
        ];
    }

    protected function to(?Role $record = null): void
    {
        if (! $record instanceof Role) {
            $this->redirect(route('internal.controls.roles.create'), navigate: true);

            return;
        }

        $this->redirect(route('internal.controls.roles.show', $record), navigate: true);
    }
}
