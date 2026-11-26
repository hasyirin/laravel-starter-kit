<?php

declare(strict_types=1);

namespace App\Livewire\DataTables\Settings;

use App\Domain\User\Models\User;
use App\Livewire\Concerns\RequiresVerification;
use App\Livewire\DataTables\SessionDataTable as DataTable;
use Filament\Tables\Table;

class SessionDataTable extends DataTable
{
    use RequiresVerification;

    public function table(Table $table): Table
    {
        return parent::table($table)
            ->heading((string) __('user/sessions.heading'))
            ->description((string) __('user/sessions.description'));
    }

    protected function user(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }
}
