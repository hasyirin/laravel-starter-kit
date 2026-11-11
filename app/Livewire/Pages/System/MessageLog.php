<?php

declare(strict_types=1);

namespace App\Livewire\Pages\System;

use App\Domain\Shared\Message\Models\Message;
use App\Livewire\Pages\Shared\Page;
use Illuminate\Contracts\View\View;

class MessageLog extends Page
{
    public function mount(): void
    {
        $this->authorize('viewAny', Message::class);
    }

    public function render(): View
    {
        return view('livewire.pages.system.message-log')->title(__('navigation.message_log'));
    }
}
