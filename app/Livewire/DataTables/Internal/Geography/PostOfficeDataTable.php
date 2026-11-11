<?php

declare(strict_types=1);

namespace App\Livewire\DataTables\Internal\Geography;

use App\Domain\Shared\Geography\Actions\DeletePostOffice;
use App\Domain\Shared\Geography\Models\PostOffice;
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
class PostOfficeDataTable extends DataTable
{
    #[Locked]
    public ?State $state = null;

    public function mount(): void
    {
        if ($this->state instanceof State) {
            $this->authorize('view', $this->state);

            return;
        }

        $this->authorize('viewAny', PostOffice::class);
    }

    public function table(Table $table): Table
    {
        return parent::table($table)->defaultSort('name');
    }

    /**
     * @return Builder<PostOffice>
     */
    protected function query(): Builder
    {
        $query = PostOffice::query()
            ->withCount('postcodes')
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
                ->label((string) __('geography/cities.city'))
                ->visible(fn (): bool => $this->state instanceof State && $this->can('create', PostOffice::class))
                ->action($this->to(...)),
        ];
    }

    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        return [
            TextColumn::make('name')->label((string) __('common/inputs.name'))->searchable()->sortable(),
            TextColumn::make('state.name')
                ->label((string) __('geography/states.state'))
                ->searchable()
                ->hidden($this->state instanceof State),
            TextColumn::make('state.country.name')
                ->label((string) __('geography/countries.country'))
                ->hidden($this->state instanceof State),
            TextColumn::make('postcodes_count')->label((string) __('geography/cities.postcodes_count'))->numeric()->sortable(),
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
                ->visible(fn (PostOffice $postOffice): bool => $this->can('view', $postOffice))
                ->action($this->to(...)),

            DeleteAction::make()
                ->through(DeletePostOffice::class)
                ->visible(fn (PostOffice $postOffice): bool => $this->can('delete', $postOffice) && ! DeleteBlocker::blocks($postOffice, null)),
        ];
    }

    protected function to(?PostOffice $record = null): void
    {
        if (! $record instanceof PostOffice) {
            $this->redirect(route('internal.controls.geography.countries.states.cities.create', ['country' => $this->state?->country_id, 'state' => $this->state]), navigate: true);

            return;
        }

        $state = $this->state ?? $record->state;

        $this->redirect(route('internal.controls.geography.countries.states.cities.show', ['country' => $state->country_id, 'state' => $state, 'postOffice' => $record]), navigate: true);
    }
}
