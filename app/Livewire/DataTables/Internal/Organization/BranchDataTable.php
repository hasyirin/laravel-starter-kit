<?php

declare(strict_types=1);

namespace App\Livewire\DataTables\Internal\Organization;

use App\Domain\Shared\Organization\Actions\DeleteBranch;
use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Organization\Support\PlacedUsers;
use App\Filament\Actions\CreateAction;
use App\Filament\Actions\DeleteAction;
use App\Filament\Actions\ViewAction;
use App\Filament\Tables\Columns\TimestampColumn;
use App\Livewire\DataTables\DataTable;
use Filament\Actions\Action;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Defer;
use Livewire\Attributes\Locked;

#[Defer]
class BranchDataTable extends DataTable
{
    #[Locked]
    public Organization $organization;

    public function mount(): void
    {
        $this->authorize('view', $this->organization);
    }

    /**
     * @return Builder<Branch>
     */
    protected function query(): Builder
    {
        return Branch::query()->whereBelongsTo($this->organization);
    }

    /**
     * @return array<int, Action>
     */
    protected function headerActions(): array
    {
        return [
            CreateAction::make()
                ->label((string) __('organization/branches.branch'))
                ->visible(fn (): bool => $this->can('create', Branch::class))
                ->action($this->to(...)),
        ];
    }

    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        return [
            TextColumn::make('code')->label((string) __('common/inputs.code'))->searchable()->sortable(),
            TextColumn::make('name')->label((string) __('common/inputs.name'))->searchable()->sortable(),
            IconColumn::make('is_owner')->label((string) __('organization/organizations.owner'))->boolean(),
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
                ->visible(fn (Branch $branch): bool => $this->can('view', $branch))
                ->action($this->to(...)),

            DeleteAction::make()
                ->through(DeleteBranch::class)
                ->visible(fn (Branch $branch): bool => $this->can('delete', $branch) && ! PlacedUsers::of($branch)->exists()),
        ];
    }

    protected function to(?Branch $record = null): void
    {
        if (! $record instanceof Branch) {
            $this->redirect(route('internal.controls.organizations.branches.create', $this->organization), navigate: true);

            return;
        }

        $this->redirect(route('internal.controls.organizations.branches.show', [$this->organization, $record]), navigate: true);
    }
}
