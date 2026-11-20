<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire\DataTables;

use Filament\Tables\Table;

class NamedPageNoteDataTable extends PlainNoteDataTable
{
    public function table(Table $table): Table
    {
        return parent::table($table)->queryStringIdentifier('notes');
    }
}
