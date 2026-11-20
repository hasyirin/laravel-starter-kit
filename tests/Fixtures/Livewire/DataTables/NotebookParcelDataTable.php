<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire\DataTables;

use App\Filament\Actions\ViewAction;
use App\Livewire\DataTables\DataTable;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Livewire\Attributes\Locked;
use Tests\Fixtures\Models\Notebook;
use Tests\Fixtures\Models\Parcel;

class NotebookParcelDataTable extends DataTable
{
    #[Locked]
    public Notebook $notebook;

    public function table(Table $table): Table
    {
        return parent::table($table)->allowDuplicates();
    }

    /**
     * @return BelongsToMany<Parcel, Notebook>
     */
    protected function relationship(): BelongsToMany
    {
        return $this->notebook->belongsToMany(Parcel::class, 'notebook_parcel')->withPivot('id');
    }

    /**
     * @return TextColumn[]
     */
    protected function columns(): array
    {
        return [TextColumn::make('label')];
    }

    /**
     * @return Action[]
     */
    protected function actions(): array
    {
        return [
            ViewAction::make()->action(fn (Parcel $parcel) => $this->dispatch('opened', id: $parcel->id)),
        ];
    }
}
