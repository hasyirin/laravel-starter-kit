<?php

declare(strict_types=1);

namespace App\Livewire\Layouts\Local;

use App\Livewire\Components\Navigation as BaseNavigation;
use App\Livewire\Components\Navigation\Group;
use App\Livewire\Components\Navigation\Item;
use App\Livewire\Components\Navigation\Spacer;
use App\Livewire\Pages\Local\ComponentDocs;

class Navigation extends BaseNavigation
{
    /** @return array<Group|Spacer|Item> */
    protected function items(): array
    {
        return [
            Group::make(label: (string) __('navigation.docs'), items: [
                Item::make(label: (string) __('docs.components'), icon: 'puzzle-piece', children: [
                    Item::make(label: (string) __('docs.overview'), route: 'docs.components', parameters: ['page' => null]),
                    ...ComponentDocs::documents()->map(fn (string $title, string $page): Item => Item::make(label: $title, route: 'docs.components', parameters: ['page' => $page]))->values(),
                ]),
            ]),
            Spacer::make(),
            Item::make(label: (string) __('navigation.app'), icon: 'arrow-uturn-left', route: 'dashboard', accessible: auth()->check()),
            Item::make(label: (string) __('navigation.internal'), icon: 'briefcase', route: 'internal.dashboard', accessible: config('system.permissions.internal.access')),
            Item::make(label: (string) __('navigation.system'), icon: 'shield-check', route: 'system.dashboard', accessible: config('system.permissions.system.access')),
        ];
    }
}
