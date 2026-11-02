<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Local\Examples;

use App\Filament\Actions\CreateAction;
use App\Filament\Actions\EditAction;
use App\Filament\Actions\ViewAction;
use App\Filament\Tables\Columns\TimestampColumn;
use Carbon\CarbonImmutable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Flux\Flux;
use Livewire\Attributes\Defer;

#[Defer]
class DataTable extends \App\Livewire\DataTables\DataTable
{
    public function table(Table $table): Table
    {
        return parent::table($table)->records(fn (): array => [
            1 => ['name' => 'Malaysia', 'code' => 'MY', 'created_at' => CarbonImmutable::parse('2026-01-15 09:30')],
            2 => ['name' => 'Singapore', 'code' => 'SG', 'created_at' => CarbonImmutable::parse('2026-02-03 14:05')],
            3 => ['name' => 'Indonesia', 'code' => 'ID', 'created_at' => CarbonImmutable::parse('2026-03-21 16:45')],
        ]);
    }

    protected function headerActions(): array
    {
        return [CreateAction::make()->action($this->showToast(...))];
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('code')->label((string) __('docs.examples.data_table.code'))->width(1),
            TextColumn::make('name')->label((string) __('docs.examples.data_table.name')),
            TimestampColumn::make('created_at')->label((string) __('docs.examples.data_table.created_at')),
        ];
    }

    protected function actions(): array
    {
        return [
            ViewAction::make()->action($this->showToast(...)),
            EditAction::make()->action($this->showToast(...)),
        ];
    }

    private function showToast(): void
    {
        Flux::toast(text: __('docs.examples.data_table.viewed'));
    }
}
