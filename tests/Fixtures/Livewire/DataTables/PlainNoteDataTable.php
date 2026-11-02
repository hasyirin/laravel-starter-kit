<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire\DataTables;

use App\Filament\Actions\DeleteAction;
use App\Filament\Actions\EditAction;
use App\Livewire\DataTables\DataTable;
use Filament\Tables\Columns\TextColumn;
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

    protected function columns(): array
    {
        return [TextColumn::make('title')];
    }

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
