<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Admin;

use App\Livewire\Pages\Shared\Page;
use Illuminate\Contracts\View\View;

class AuditLog extends Page
{
    public function mount(): void
    {
        $this->authorize(config()->string('system.permissions.system.audit-log.view'));
    }

    public function render(): View
    {
        return view('livewire.pages.admin.audit-log')->title(__('navigation.audit_log'));
    }
}
