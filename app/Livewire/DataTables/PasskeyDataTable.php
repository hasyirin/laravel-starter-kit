<?php

declare(strict_types=1);

namespace App\Livewire\DataTables;

use App\Filament\Tables\Columns\TimestampColumn;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;

abstract class PasskeyDataTable extends DataTable
{
    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        return [
            TextColumn::make('name')->label((string) __('user/users.passkeys_tab.name')),
            TextColumn::make('authenticator')->label((string) __('user/users.passkeys_tab.authenticator')),
            TimestampColumn::make('created_at')->label((string) __('user/users.passkeys_tab.created_at')),
            TimestampColumn::make('last_used_at')->label((string) __('user/users.passkeys_tab.last_used_at')),
        ];
    }
}
