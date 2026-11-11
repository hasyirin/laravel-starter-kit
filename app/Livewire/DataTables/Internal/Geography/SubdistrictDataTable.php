<?php

declare(strict_types=1);

namespace App\Livewire\DataTables\Internal\Geography;

use App\Domain\Shared\Geography\Actions\DeleteSubdistrict;
use App\Domain\Shared\Geography\Models\District;
use App\Domain\Shared\Geography\Models\Subdistrict;
use App\Domain\Shared\Geography\Support\DeleteBlocker;
use App\Filament\Actions\CreateAction;
use App\Filament\Actions\DeleteAction;
use App\Filament\Actions\ViewAction;
use App\Filament\Tables\Columns\TimestampColumn;
use App\Livewire\DataTables\DataTable;
use App\Livewire\Pages\Internal\Controls\Geography\SubdistrictModal;
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
class SubdistrictDataTable extends DataTable
{
    #[Locked]
    public ?District $district = null;

    public function mount(): void
    {
        if ($this->district instanceof District) {
            $this->authorize('view', $this->district);

            return;
        }

        $this->authorize('viewAny', Subdistrict::class);
    }

    public function table(Table $table): Table
    {
        return parent::table($table)->defaultSort('name');
    }

    /**
     * @return array<int|string, string>
     */
    protected function getListeners(): array
    {
        return [...parent::getListeners(), SubdistrictModal::event() => '$refresh'];
    }

    /**
     * @return Builder<Subdistrict>
     */
    protected function query(): Builder
    {
        $query = Subdistrict::query()->tap(DeleteBlocker::withAddresses(...));

        if ($this->district instanceof District) {
            return $query->whereBelongsTo($this->district);
        }

        return $query->with('district.state')->localFirst();
    }

    /**
     * @return array<int, Action>
     */
    protected function headerActions(): array
    {
        return [
            CreateAction::make()
                ->label((string) __('geography/subdistricts.subdistrict'))
                ->visible(fn (): bool => $this->district instanceof District && $this->can('create', Subdistrict::class))
                ->action(fn () => $this->dispatch('show')->to(SubdistrictModal::class)),
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
            TextColumn::make('district.name')
                ->label((string) __('geography/districts.district'))
                ->searchable()
                ->hidden($this->district instanceof District),
            TextColumn::make('district.state.name')
                ->label((string) __('geography/states.state'))
                ->hidden($this->district instanceof District),
            TimestampColumn::make('created_at'),
        ];
    }

    /**
     * @return array<int, BaseFilter>
     */
    protected function filters(): array
    {
        if ($this->district instanceof District) {
            return [];
        }

        return [
            SelectFilter::make('district')
                ->label((string) __('geography/districts.district'))
                ->relationship('district', 'name')
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
                ->visible(fn (Subdistrict $subdistrict): bool => $this->can('view', $subdistrict))
                ->action(fn (Subdistrict $subdistrict) => $this->dispatch('show', id: $subdistrict->id)->to(SubdistrictModal::class)),

            DeleteAction::make()
                ->through(DeleteSubdistrict::class)
                ->visible(fn (Subdistrict $subdistrict): bool => $this->can('delete', $subdistrict) && ! DeleteBlocker::blocks($subdistrict, null)),
        ];
    }
}
