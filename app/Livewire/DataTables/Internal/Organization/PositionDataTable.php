<?php

declare(strict_types=1);

namespace App\Livewire\DataTables\Internal\Organization;

use App\Domain\Shared\Organization\Actions\DeletePosition;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Organization\Models\Position;
use App\Domain\Shared\Organization\Support\PlacedUsers;
use App\Filament\Actions\CreateAction;
use App\Filament\Actions\DeleteAction;
use App\Filament\Actions\ViewAction;
use App\Filament\Tables\Columns\TimestampColumn;
use App\Livewire\DataTables\DataTable;
use App\Livewire\Pages\Internal\Controls\Organization\PositionModal;
use Filament\Actions\Action;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Livewire\Attributes\Defer;
use Livewire\Attributes\Locked;

#[Defer]
class PositionDataTable extends DataTable
{
    #[Locked]
    public Organization $organization;

    #[Locked]
    public ?Department $department = null;

    public function mount(): void
    {
        $this->authorize('view', $this->organization);
    }

    /**
     * @return array<int|string, string>
     */
    protected function getListeners(): array
    {
        return [...parent::getListeners(), PositionModal::event() => '$refresh'];
    }

    /**
     * @return Builder<Position>
     */
    protected function query(): Builder
    {
        $query = Position::query()
            ->whereBelongsTo($this->organization)
            ->with(['department' => fn (BelongsTo $department): BelongsTo => $department->withTrashed()]);

        if ($this->department instanceof Department) {
            $query->whereBelongsTo($this->department);
        }

        return $query;
    }

    /**
     * @return array<int, Action>
     */
    protected function headerActions(): array
    {
        return [
            CreateAction::make()
                ->label((string) __('organization/positions.position'))
                ->visible(fn (): bool => $this->can('create', Position::class))
                ->action(fn () => $this->dispatch('show')->to(PositionModal::class)),
        ];
    }

    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        return [
            TextColumn::make('name')->label((string) __('common/inputs.name'))->searchable()->sortable(),
            TextColumn::make('department.name')
                ->label((string) __('organization/departments.department'))
                ->placeholder((string) __('organization/positions.no_department'))
                ->hidden($this->department instanceof Department),
            TimestampColumn::make('created_at'),
        ];
    }

    /**
     * @return array<int, BaseFilter>
     */
    protected function filters(): array
    {
        if ($this->department instanceof Department) {
            return [];
        }

        return [
            SelectFilter::make('department')
                ->label((string) __('organization/departments.department'))
                ->relationship('department', 'name', fn (Builder $query): Builder => $query->whereBelongsTo($this->organization)),
        ];
    }

    /**
     * @return array<int, Action>
     */
    protected function actions(): array
    {
        return [
            ViewAction::make()
                ->visible(fn (Position $position): bool => $this->can('view', $position))
                ->action(fn (Position $position) => $this->dispatch('show', id: $position->id)->to(PositionModal::class)),

            DeleteAction::make()
                ->through(DeletePosition::class)
                ->visible(fn (Position $position): bool => $this->can('delete', $position) && ! PlacedUsers::of($position)->exists()),
        ];
    }
}
