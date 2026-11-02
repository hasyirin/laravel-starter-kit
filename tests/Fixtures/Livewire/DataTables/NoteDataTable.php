<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire\DataTables;

use App\Filament\Actions\CreateAction;
use App\Filament\Actions\DeleteAction;
use App\Filament\Actions\ViewAction;
use App\Filament\Tables\Columns\TimestampColumn;
use App\Livewire\DataTables\DataTable;
use Filament\Actions\Action;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Livewire\Attributes\Locked;
use Tests\Fixtures\Actions\DeleteNote;
use Tests\Fixtures\Livewire\NoteModal;
use Tests\Fixtures\Models\Note;
use Tests\Fixtures\Models\Notebook;

class NoteDataTable extends DataTable
{
    #[Locked]
    public Notebook $notebook;

    /**
     * @return HasMany<Note, Notebook>
     */
    protected function relationship(): HasMany
    {
        return $this->notebook->notes();
    }

    /**
     * @return CreateAction[]
     */
    protected function headerActions(): array
    {
        return [
            CreateAction::make()->action(fn () => $this->dispatch('show')->to(NoteModal::class)),
        ];
    }

    /**
     * @return Column[]
     */
    protected function columns(): array
    {
        return [
            TextColumn::make('title')->searchable()->sortable(),
            TimestampColumn::make('created_at'),
        ];
    }

    /**
     * @return Action[]
     */
    protected function actions(): array
    {
        return [
            ViewAction::make()
                ->visible(fn (Note $note): bool => $this->can('view', $note))
                ->action(fn (Note $note) => $this->dispatch('show', id: $note->id)->to(NoteModal::class)),

            DeleteAction::make()
                ->through(DeleteNote::class)
                ->visible(fn (Note $note): bool => $this->can('delete', $note))
                ->disabled(fn (Note $note): bool => $note->title === 'Protected'),
        ];
    }

    protected function getListeners(): array
    {
        return [...parent::getListeners(), NoteModal::event() => '$refresh'];
    }
}
