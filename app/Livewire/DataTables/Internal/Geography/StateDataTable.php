<?php

declare(strict_types=1);

namespace App\Livewire\DataTables\Internal\Geography;

use App\Domain\Shared\Geography\Actions\DeleteState;
use App\Domain\Shared\Geography\Models\Country;
use App\Domain\Shared\Geography\Models\State;
use App\Domain\Shared\Geography\Support\DeleteBlocker;
use App\Filament\Actions\CreateAction;
use App\Filament\Actions\DeleteAction;
use App\Filament\Actions\ViewAction;
use App\Filament\Tables\Columns\TimestampColumn;
use App\Livewire\DataTables\DataTable;
use Filament\Actions\Action;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Defer;
use Livewire\Attributes\Locked;

#[Defer]
class StateDataTable extends DataTable
{
    #[Locked]
    public ?Country $country = null;

    public function mount(): void
    {
        if ($this->country instanceof Country) {
            $this->authorize('view', $this->country);

            return;
        }

        $this->authorize('viewAny', State::class);
    }

    public function table(Table $table): Table
    {
        return parent::table($table)->defaultSort('name');
    }

    /**
     * @return Builder<State>
     */
    protected function query(): Builder
    {
        $query = State::query()
            ->withCount(['districts', 'postOffices'])
            ->tap(DeleteBlocker::withAddresses(...))
            ->localFirst();

        if ($this->country instanceof Country) {
            return $query->whereBelongsTo($this->country);
        }

        return $query->with('country');
    }

    /**
     * @return array<int, Action>
     */
    protected function headerActions(): array
    {
        return [
            CreateAction::make()
                ->label((string) __('geography/states.state'))
                ->visible(fn (): bool => $this->country instanceof Country && $this->can('create', State::class))
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
            TextColumn::make('country.name')
                ->label((string) __('geography/countries.country'))
                ->searchable()
                ->hidden($this->country instanceof Country),
            TextColumn::make('districts_count')->label((string) __('geography/states.districts_count'))->numeric()->sortable(),
            TextColumn::make('post_offices_count')->label((string) __('geography/states.cities_count'))->numeric()->sortable(),
            TimestampColumn::make('created_at'),
        ];
    }

    /**
     * @return array<int, BaseFilter>
     */
    protected function filters(): array
    {
        if ($this->country instanceof Country) {
            return [];
        }

        return [
            SelectFilter::make('country')
                ->label((string) __('geography/countries.country'))
                ->relationship('country', 'name')
                ->searchable(),
        ];
    }

    /**
     * @return array<int, Action>
     */
    protected function actions(): array
    {
        return [
            ViewAction::make()
                ->visible(fn (State $record): bool => $this->can('view', $record))
                ->action($this->to(...)),

            DeleteAction::make()
                ->through(DeleteState::class)
                ->visible(fn (State $record): bool => $this->can('delete', $record) && ! DeleteBlocker::blocks($record, $this->locality())),
        ];
    }

    protected function locality(): ?State
    {
        return once(fn (): ?State => State::local());
    }

    protected function to(?State $record = null): void
    {
        if (! $record instanceof State) {
            $this->redirect(route('internal.controls.geography.countries.states.create', $this->country), navigate: true);

            return;
        }

        $this->redirect(route('internal.controls.geography.countries.states.show', ['country' => $record->country_id, 'state' => $record]), navigate: true);
    }
}
