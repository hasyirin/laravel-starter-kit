<?php

declare(strict_types=1);

namespace App\Livewire\Pages\System;

use App\Domain\Shared\Mail\Models\Mail;
use App\Livewire\Pages\Shared\Page;
use Illuminate\Contracts\View\View;

class MailLog extends Page
{
    public function mount(): void
    {
        $this->authorize('viewAny', Mail::class);
    }

    public function render(): View
    {
        return view('livewire.pages.system.mail-log')->title(__('navigation.mail_log'));
    }
}
