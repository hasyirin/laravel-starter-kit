<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Admin\Controls\User;

use App\Domain\User\Models\User;
use App\Livewire\Component;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;

class Audit extends Component
{
    #[Locked]
    public int $userId;

    #[Url]
    public string $direction = 'by';

    public function mount(): void
    {
        $this->authorize('view', [$this->user, 'activities']);
    }

    #[Computed]
    public function user(): User
    {
        return User::query()->findOrFail($this->userId);
    }

    public function render(): View
    {
        return view('livewire.pages.admin.controls.user.audit');
    }
}
