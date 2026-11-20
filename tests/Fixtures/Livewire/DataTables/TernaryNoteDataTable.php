<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire\DataTables;

use App\Livewire\DataTables\DataTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;
use Tests\Fixtures\Models\Note;

class TernaryNoteDataTable extends DataTable
{
    /**
     * @return Builder<Note>
     */
    protected function query(): Builder
    {
        return Note::query();
    }

    /**
     * @return TextColumn[]
     */
    protected function columns(): array
    {
        return [TextColumn::make('title')];
    }

    /**
     * @return TernaryFilter[]
     */
    protected function filters(): array
    {
        return [
            TrashedFilter::make(),
            TernaryFilter::make('labelled')->relationship('parcel', 'label')->nullable(),
        ];
    }
}
