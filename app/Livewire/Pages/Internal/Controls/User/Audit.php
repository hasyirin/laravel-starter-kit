<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\User;

use App\Domain\User\Enums\AuditDirection;
use App\Domain\User\Models\User;
use App\Livewire\Component;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;

class Audit extends Component
{
    #[Locked]
    public User $user;

    #[Url(nullable: true)]
    public AuditDirection $direction = AuditDirection::By {
        // Livewire puts null here when ?direction= has an unknown value. Without nullable: true, it throws a ValueError.
        set(?AuditDirection $direction) => $direction ?? AuditDirection::By;
    }

    public function mount(): void
    {
        $this->authorize('view', [$this->user, 'audit-log']);
    }

    public function render(): View
    {
        return view('livewire.pages.internal.controls.user.audit');
    }
}
