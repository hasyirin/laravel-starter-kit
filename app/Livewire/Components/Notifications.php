<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Livewire\Concerns\HidesBroadcastChannel;
use Filament\Notifications\Livewire\Notifications as FilamentNotifications;

class Notifications extends FilamentNotifications
{
    use HidesBroadcastChannel;
}
