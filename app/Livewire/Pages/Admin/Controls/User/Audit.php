<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Admin\Controls\User;

use App\Domain\User\Models\User;
use App\Livewire\Component;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;

class Audit extends Component
{
    #[Locked]
    public User $user;

    #[Url]
    public string $direction = 'by';

    public function mount(): void
    {
        $this->authorize('view', [$this->user, 'activities']);
    }

    public function render(): View
    {
        return view('livewire.pages.admin.controls.user.audit');
    }
}
