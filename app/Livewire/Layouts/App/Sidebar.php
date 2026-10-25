<?php

declare(strict_types=1);

namespace App\Livewire\Layouts\App;

use App\Livewire\Components\Navigation;
use App\Livewire\Components\Navigation\Group;
use App\Livewire\Components\Navigation\Item;
use App\Livewire\Components\Navigation\Spacer;

class Sidebar extends Navigation
{
    /** @return array<Group|Spacer|Item> */
    protected function items(): array
    {
        return [
            Item::make(label: (string) __('navigation.dashboard'), icon: 'home', route: 'dashboard'),
            Spacer::make(),
            Item::make(label: (string) __('navigation.internal'), icon: 'briefcase', route: 'internal.dashboard', accessible: config('system.permissions.internal.access')),
            Item::make(label: (string) __('navigation.admin'), icon: 'shield-check', route: 'admin.dashboard', accessible: config('system.permissions.admin.access')),
        ];
    }
}
