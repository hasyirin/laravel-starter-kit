<?php

declare(strict_types=1);

namespace App\Livewire\DataTables\Internal\Geography;

use App\Domain\Shared\Geography\Actions\DeleteCountry;
use App\Domain\Shared\Geography\Models\Country;
use App\Domain\Shared\Geography\Support\DeleteBlocker;
use App\Filament\Actions\CreateAction;
use App\Filament\Actions\DeleteAction;
use App\Filament\Actions\ViewAction;
use App\Filament\Tables\Columns\TimestampColumn;
use App\Livewire\DataTables\DataTable;
use Filament\Actions\Action;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Defer;

#[Defer]
class CountryDataTable extends DataTable
{
    public function mount(): void
    {
        $this->authorize('viewAny', Country::class);
    }

    public function table(Table $table): Table
    {
        return parent::table($table)->defaultSort('name');
    }

    /**
     * @return Builder<Country>
     */
    protected function query(): Builder
    {
        return Country::query()
            ->withCount('states')
            ->tap(DeleteBlocker::withAddresses(...))
            ->localFirst();
    }

    /**
     * @return array<int, Action>
     */
    protected function headerActions(): array
    {
        return [
            CreateAction::make()
                ->label((string) __('geography/countries.country'))
                ->visible(fn (): bool => $this->can('create', Country::class))
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
            TextColumn::make('alpha_2')->label((string) __('common/inputs.alpha_2'))->searchable()->sortable(),
            TextColumn::make('states_count')->label((string) __('geography/countries.states_count'))->numeric()->sortable(),
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
                ->visible(fn (Country $country): bool => $this->can('view', $country))
                ->action($this->to(...)),

            DeleteAction::make()
                ->through(DeleteCountry::class)
                ->visible(fn (Country $country): bool => $this->can('delete', $country) && ! DeleteBlocker::blocks($country, $this->locality())),
        ];
    }

    protected function locality(): ?Country
    {
        return once(fn (): ?Country => Country::local());
    }

    protected function to(?Country $record = null): void
    {
        if (! $record instanceof Country) {
            $this->redirect(route('internal.controls.geography.countries.create'), navigate: true);

            return;
        }

        $this->redirect(route('internal.controls.geography.countries.show', $record), navigate: true);
    }
}
