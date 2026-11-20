<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire\DataTables;

use App\Filament\Actions\DeleteAction;
use App\Filament\Actions\EditAction;
use App\Livewire\DataTables\DataTable;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Tests\Fixtures\Models\Note;

class PlainNoteDataTable extends DataTable
{
    public ?string $url = null;

    public bool $withDeleteAction = false;

    public bool $withEditAction = false;

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
     * @return SelectFilter[]
     */
    protected function filters(): array
    {
        return [
            SelectFilter::make('notebook')->relationship('notebook', 'id'),
            SelectFilter::make('notebooks')->relationship('notebook', 'id', hasEmptyOption: true)->multiple(),
            SelectFilter::make('parcel')->relationship('parcel', 'label'),
            SelectFilter::make('title')->options(['Apple' => 'Apple', 'Pear' => 'Pear'])->multiple(),
        ];
    }

    /**
     * @return Action[]
     */
    protected function actions(): array
    {
        return array_values(array_filter([
            $this->withEditAction ? EditAction::make()->visible(fn (Note $note): bool => ! $note->locked) : null,
            $this->withDeleteAction ? DeleteAction::make() : null,
        ]));
    }

    protected function recordUrl(Model $record): ?string
    {
        return $this->url;
    }
}
