<?php

declare(strict_types=1);

namespace App\Livewire\Layouts\Internal;

use App\Domain\Shared\Permission\Models\Role;
use App\Domain\User\Models\User;
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
            Item::make(label: (string) __('navigation.dashboard'), icon: 'home', route: 'internal.dashboard'),
            Item::make(label: (string) __('navigation.controls'), icon: 'adjustments-horizontal', children: [
                Item::make(label: (string) __('navigation.users'), icon: 'users', route: 'internal.controls.users.index', active: 'internal.controls.users.*', accessible: ['viewAny', User::class]),
                Item::make(label: (string) __('navigation.roles'), icon: 'shield-check', route: 'internal.controls.roles.index', active: 'internal.controls.roles.*', accessible: ['viewAny', Role::class]),
            ]),
            Spacer::make(),
            Item::make(label: (string) __('navigation.system'), icon: 'shield-check', route: 'system.dashboard', accessible: config('system.permissions.system.access')),
            Item::make(label: (string) __('navigation.app'), icon: 'arrow-uturn-left', route: 'dashboard'),
            Item::make(label: (string) __('navigation.docs'), icon: 'book-open', route: 'docs.components', accessible: app()->isLocal()),
        ];
    }
}
