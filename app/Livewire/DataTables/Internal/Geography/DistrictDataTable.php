<?php

declare(strict_types=1);

namespace App\Livewire\DataTables\Internal\Geography;

use App\Domain\Shared\Geography\Actions\DeleteDistrict;
use App\Domain\Shared\Geography\Models\District;
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
class DistrictDataTable extends DataTable
{
    #[Locked]
    public ?State $state = null;

    public function mount(): void
    {
        if ($this->state instanceof State) {
            $this->authorize('view', $this->state);

            return;
        }

        $this->authorize('viewAny', District::class);
    }

    public function table(Table $table): Table
    {
        return parent::table($table)->defaultSort('name');
    }

    /**
     * @return Builder<District>
     */
    protected function query(): Builder
    {
        $query = District::query()
            ->withCount('subdistricts')
            ->tap(DeleteBlocker::withAddresses(...));

        if ($this->state instanceof State) {
            return $query->whereBelongsTo($this->state);
        }

        return $query->with('state.country')->localFirst();
    }

    /**
     * @return array<int, Action>
     */
    protected function headerActions(): array
    {
        return [
            CreateAction::make()
                ->label((string) __('geography/districts.district'))
                ->visible(fn (): bool => $this->state instanceof State && $this->can('create', District::class))
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
            TextColumn::make('state.name')
                ->label((string) __('geography/states.state'))
                ->searchable()
                ->hidden($this->state instanceof State),
            TextColumn::make('state.country.name')
                ->label((string) __('geography/countries.country'))
                ->hidden($this->state instanceof State),
            TextColumn::make('subdistricts_count')->label((string) __('geography/districts.subdistricts_count'))->numeric()->sortable(),
            TimestampColumn::make('created_at'),
        ];
    }

    /**
     * @return array<int, BaseFilter>
     */
    protected function filters(): array
    {
        if ($this->state instanceof State) {
            return [];
        }

        return [
            SelectFilter::make('state')
                ->label((string) __('geography/states.state'))
                ->relationship('state', 'name')
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
                ->visible(fn (District $district): bool => $this->can('view', $district))
                ->action($this->to(...)),

            DeleteAction::make()
                ->through(DeleteDistrict::class)
                ->visible(fn (District $district): bool => $this->can('delete', $district) && ! DeleteBlocker::blocks($district, $this->locality())),
        ];
    }

    protected function locality(): ?District
    {
        return once(fn (): ?District => District::local());
    }

    protected function to(?District $record = null): void
    {
        if (! $record instanceof District) {
            $this->redirect(route('internal.controls.geography.countries.states.districts.create', ['country' => $this->state?->country_id, 'state' => $this->state]), navigate: true);

            return;
        }

        $state = $this->state ?? $record->state;

        $this->redirect(route('internal.controls.geography.countries.states.districts.show', ['country' => $state->country_id, 'state' => $state, 'district' => $record]), navigate: true);
    }
}
