<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\User;

use App\Domain\User\Models\User;
use App\Livewire\Pages\Shared\Page;
use Illuminate\Contracts\View\View;

class Index extends Page
{
    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function render(): View
    {
        return view('livewire.pages.internal.controls.user.index')->title(__('user/users.title'));
    }
}
