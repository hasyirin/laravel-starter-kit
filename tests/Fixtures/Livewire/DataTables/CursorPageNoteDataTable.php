<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire\DataTables;

use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Table;

class CursorPageNoteDataTable extends PlainNoteDataTable
{
    public function table(Table $table): Table
    {
        return parent::table($table)->paginationMode(PaginationMode::Cursor);
    }
}
