<?php

declare(strict_types=1);

namespace App\Livewire\DataTables\Internal\Organization;

use App\Domain\Shared\Letter\Actions\DeleteLetterhead;
use App\Domain\Shared\Letter\Models\Letterhead;
use App\Domain\Shared\Organization\Models\Organization;
use App\Filament\Actions\CreateAction;
use App\Filament\Actions\DeleteAction;
use App\Filament\Actions\ViewAction;
use App\Filament\Tables\Columns\TimestampColumn;
use App\Livewire\DataTables\DataTable;
use App\Livewire\Pages\Internal\Controls\Organization\LetterheadModal;
use Filament\Actions\Action;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Livewire\Attributes\Defer;
use Livewire\Attributes\Locked;

#[Defer]
class LetterheadDataTable extends DataTable
{
    #[Locked]
    public Organization $organization;

    public function mount(): void
    {
        $this->authorize('view', $this->organization);
        $this->authorize('viewAny', Letterhead::class);
    }

    /**
     * @return array<int|string, string>
     */
    protected function getListeners(): array
    {
        return [...parent::getListeners(), LetterheadModal::event() => '$refresh'];
    }

    /**
     * @return Builder<Letterhead>
     */
    protected function query(): Builder
    {
        return Letterhead::query()
            ->whereBelongsTo($this->organization)
            ->with([
                'branch' => fn (BelongsTo $branch): BelongsTo => $branch->withTrashed(),
                'department' => fn (BelongsTo $department): BelongsTo => $department->withTrashed(),
            ]);
    }

    /**
     * @return array<int, Action>
     */
    protected function headerActions(): array
    {
        return [
            CreateAction::make()
                ->label((string) __('letters/letterheads.letterhead'))
                ->visible(fn (): bool => $this->can('create', Letterhead::class))
                ->action(fn () => $this->dispatch('show')->to(LetterheadModal::class)),
        ];
    }

    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        return [
            TextColumn::make('name')->label((string) __('common/inputs.name'))->searchable()->sortable(),
            TextColumn::make('branch.name')
                ->label((string) __('organization/branches.branch'))
                ->placeholder((string) __('letters/letterheads.all_branches')),
            TextColumn::make('department.name')
                ->label((string) __('organization/departments.department'))
                ->placeholder((string) __('letters/letterheads.all_departments')),
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
                ->visible(fn (Letterhead $letterhead): bool => $this->can('view', $letterhead))
                ->action(fn (Letterhead $letterhead) => $this->dispatch('show', id: $letterhead->id)->to(LetterheadModal::class)),

            DeleteAction::make()
                ->through(DeleteLetterhead::class)
                ->visible(fn (Letterhead $letterhead): bool => $this->can('delete', $letterhead)),
        ];
    }
}
