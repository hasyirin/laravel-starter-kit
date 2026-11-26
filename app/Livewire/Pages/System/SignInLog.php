<?php

declare(strict_types=1);

namespace App\Livewire\Pages\System;

use App\Domain\User\Models\SignInEntry;
use App\Livewire\Pages\Shared\Page;
use Illuminate\Contracts\View\View;

class SignInLog extends Page
{
    public function mount(): void
    {
        $this->authorize('viewAny', SignInEntry::class);
    }

    public function render(): View
    {
        return view('livewire.pages.system.sign-in-log')->title(__('navigation.sign_in_log'));
    }
}
