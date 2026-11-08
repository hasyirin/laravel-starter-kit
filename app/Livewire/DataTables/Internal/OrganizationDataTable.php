<?php

declare(strict_types=1);

namespace App\Livewire\DataTables\Internal;

use App\Domain\Shared\Organization\Actions\DeleteOrganization;
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

#[Defer]
class OrganizationDataTable extends DataTable
{
    public function mount(): void
    {
        $this->authorize('viewAny', Organization::class);
    }

    /**
     * @return Builder<Organization>
     */
    protected function query(): Builder
    {
        return Organization::query()->withCount(['branches', 'departments']);
    }

    /**
     * @return array<int, Action>
     */
    protected function headerActions(): array
    {
        return [
            CreateAction::make()
                ->label((string) __('organization/organizations.organization'))
                ->visible(fn (): bool => $this->can('create', Organization::class))
                ->action($this->to(...)),
        ];
    }

    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        return [
            TextColumn::make('code')
                ->label((string) __('common/inputs.code'))
                ->searchable()
                ->sortable(),
            TextColumn::make('name')
                ->label((string) __('common/inputs.name'))
                ->searchable()
                ->sortable(),
            IconColumn::make('is_owner')
                ->label((string) __('organization/organizations.owner'))
                ->boolean(),
            TextColumn::make('branches_count')
                ->label((string) __('organization/organizations.branches_count'))
                ->numeric()
                ->sortable(),
            TextColumn::make('departments_count')
                ->label((string) __('organization/organizations.departments_count'))
                ->numeric()
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
                ->visible(fn (Organization $organization): bool => $this->can('view', $organization))
                ->action($this->to(...)),

            DeleteAction::make()
                ->through(DeleteOrganization::class)
                ->visible(fn (Organization $organization): bool => $this->can('delete', $organization) && ! PlacedUsers::of($organization)->exists()),
        ];
    }

    protected function to(?Organization $record = null): void
    {
        if (! $record instanceof Organization) {
            $this->redirect(route('internal.controls.organizations.create'), navigate: true);

            return;
        }

        $this->redirect(route('internal.controls.organizations.show', $record), navigate: true);
    }
}
