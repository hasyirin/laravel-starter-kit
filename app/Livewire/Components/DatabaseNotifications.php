<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Livewire\Concerns\HidesBroadcastChannel;
use Filament\Notifications\Livewire\DatabaseNotifications as FilamentDatabaseNotifications;

class DatabaseNotifications extends FilamentDatabaseNotifications
{
    use HidesBroadcastChannel;
}
