<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire\DataTables;

use App\Filament\Tables\Columns\ActColumn;
use App\Livewire\DataTables\DataTable;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Tests\Fixtures\Models\Invoice;

class InvoiceDataTable extends DataTable
{
    /**
     * @return Builder<Invoice>
     */
    protected function query(): Builder
    {
        return Invoice::query();
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('number'),
            ActColumn::make('preparer'),
        ];
    }
}
