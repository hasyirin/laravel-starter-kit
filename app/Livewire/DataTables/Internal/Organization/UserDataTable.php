<?php

declare(strict_types=1);

namespace App\Livewire\DataTables\Internal\Organization;

use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Organization\Support\PlacedUsers;
use App\Domain\User\Models\User;
use App\Filament\Actions\ViewAction;
use App\Filament\Tables\Columns\TimestampColumn;
use App\Livewire\DataTables\DataTable;
use Filament\Actions\Action;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Defer;
use Livewire\Attributes\Locked;

#[Defer]
class UserDataTable extends DataTable
{
    #[Locked]
    public Organization|Department|Branch $record;

    public function mount(): void
    {
        $this->authorize('view', $this->record);
        $this->authorize('viewAny', User::class);
    }

    /**
     * @return Builder<User>
     */
    protected function query(): Builder
    {
        return PlacedUsers::of($this->record);
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
                ->action(fn (User $user) => $this->redirect(route('internal.controls.users.show', $user), navigate: true)),
        ];
    }
}
