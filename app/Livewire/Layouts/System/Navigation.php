<?php

declare(strict_types=1);

namespace App\Livewire\Layouts\System;

use App\Livewire\Components\Navigation as BaseNavigation;
use App\Livewire\Components\Navigation\Group;
use App\Livewire\Components\Navigation\Item;
use App\Livewire\Components\Navigation\Spacer;

class Navigation extends BaseNavigation
{
    /** @return array<Group|Spacer|Item> */
    protected function items(): array
    {
        return [
            Item::make(label: (string) __('navigation.dashboard'), icon: 'home', route: 'system.dashboard'),
            Item::make(label: (string) __('navigation.horizon'), icon: 'queue-list', href: route('horizon.index'), accessible: config('system.permissions.system.horizon.view')),
            Item::make(label: (string) __('navigation.audit_log'), icon: 'clipboard-document-list', route: 'system.audit-log', accessible: config('system.permissions.system.audit-log.view')),
            Item::make(label: (string) __('navigation.manual_letter'), icon: 'document-text', route: 'system.letters.compose', accessible: config('system.permissions.system.letters.compose')),
            Spacer::make(),
            Item::make(label: (string) __('navigation.internal'), icon: 'briefcase', route: 'internal.dashboard', accessible: config('system.permissions.internal.access')),
            Item::make(label: (string) __('navigation.app'), icon: 'arrow-uturn-left', route: 'dashboard'),
            Item::make(label: (string) __('navigation.docs'), icon: 'book-open', route: 'docs.components', accessible: app()->isLocal()),
        ];
    }
}
