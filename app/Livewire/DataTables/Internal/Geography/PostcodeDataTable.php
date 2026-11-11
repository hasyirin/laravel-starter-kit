<?php

declare(strict_types=1);

namespace App\Livewire\DataTables\Internal\Geography;

use App\Domain\Shared\Geography\Actions\DeletePostcode;
use App\Domain\Shared\Geography\Models\Postcode;
use App\Domain\Shared\Geography\Models\PostOffice;
use App\Filament\Actions\CreateAction;
use App\Filament\Actions\DeleteAction;
use App\Filament\Actions\ViewAction;
use App\Filament\Tables\Columns\TimestampColumn;
use App\Livewire\DataTables\DataTable;
use App\Livewire\Pages\Internal\Controls\Geography\PostcodeModal;
use Filament\Actions\Action;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Defer;
use Livewire\Attributes\Locked;

#[Defer]
class PostcodeDataTable extends DataTable
{
    #[Locked]
    public ?PostOffice $postOffice = null;

    public function mount(): void
    {
        if ($this->postOffice instanceof PostOffice) {
            $this->authorize('view', $this->postOffice);

            return;
        }

        $this->authorize('viewAny', Postcode::class);
    }

    public function table(Table $table): Table
    {
        return parent::table($table)
            ->defaultSort('code')
            ->splitSearchTerms(false);
    }

    /**
     * @return array<int|string, string>
     */
    protected function getListeners(): array
    {
        return [...parent::getListeners(), PostcodeModal::event() => '$refresh'];
    }

    /**
     * @return Builder<Postcode>
     */
    protected function query(): Builder
    {
        $query = Postcode::query();

        if ($this->postOffice instanceof PostOffice) {
            return $query->whereBelongsTo($this->postOffice);
        }

        return $query->with('postOffice.state.country')->localFirst();
    }

    /**
     * @return array<int, Action>
     */
    protected function headerActions(): array
    {
        return [
            CreateAction::make()
                ->label((string) __('geography/postcodes.postcode'))
                ->visible(fn (): bool => $this->postOffice instanceof PostOffice && $this->can('create', Postcode::class))
                ->action(fn () => $this->dispatch('show')->to(PostcodeModal::class)),
        ];
    }

    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        return [
            TextColumn::make('code')
                ->label((string) __('geography/postcodes.postcode'))
                ->searchable(query: $this->searchCode(...))
                ->sortable(),
            TextColumn::make('postOffice.state.country.name')
                ->label((string) __('geography/countries.country'))
                ->sortable()
                ->hidden($this->postOffice instanceof PostOffice),
            TextColumn::make('postOffice.state.name')
                ->label((string) __('geography/states.state'))
                ->sortable()
                ->hidden($this->postOffice instanceof PostOffice),
            TextColumn::make('postOffice.name')
                ->label((string) __('geography/cities.city'))
                ->sortable()
                ->hidden($this->postOffice instanceof PostOffice),
            TextColumn::make('location')
                ->label((string) __('common/inputs.location'))
                ->state($this->location(...)),
            TimestampColumn::make('created_at'),
        ];
    }

    protected function location(Postcode $postcode): ?string
    {
        if ($postcode->latitude === null || $postcode->longitude === null) {
            return null;
        }

        return "{$postcode->latitude}, {$postcode->longitude}";
    }

    /**
     * The postcodes table is large, so the search matches only the start of the code. Then the index on the code can serve it.
     * SQLite has no default escape character for LIKE, so the search names `!` as one. The table query joins no table, so `code` needs no table name.
     *
     * @param  Builder<Postcode>  $query
     * @return Builder<Postcode>
     */
    protected function searchCode(Builder $query, string $search): Builder
    {
        $prefix = str($search)
            ->squish()
            ->upper()
            ->replace(['!', '%', '_'], ['!!', '!%', '!_']);

        return $query->whereRaw("code like ? escape '!'", ["{$prefix}%"]);
    }

    /**
     * @return array<int, Action>
     */
    protected function actions(): array
    {
        return [
            ViewAction::make()
                ->visible(fn (Postcode $postcode): bool => $this->can('view', $postcode))
                ->action(fn (Postcode $postcode) => $this->dispatch('show', id: $postcode->id)->to(PostcodeModal::class)),

            DeleteAction::make()
                ->through(DeletePostcode::class)
                ->visible(fn (Postcode $postcode): bool => $this->can('delete', $postcode)),
        ];
    }
}
