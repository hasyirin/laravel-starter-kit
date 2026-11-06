<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\User;

use App\Domain\User\Models\User;
use App\Livewire\Component;
use App\Livewire\Components\Tab;
use App\Livewire\Concerns\InteractsWithTabs;
use App\Livewire\DataTables\Internal\User\PasskeyDataTable;
use Illuminate\Contracts\View\View;
use Laravel\Fortify\Features;
use Livewire\Attributes\Locked;

class Tabs extends Component
{
    use InteractsWithTabs;

    #[Locked]
    public User $user;

    /**
     * @return list<Tab>
     */
    public function tabs(): array
    {
        return [
            Tab::make('passkeys')
                ->label((string) __('user/users.tabs.passkeys'))
                ->content(PasskeyDataTable::class)
                ->params(['user' => $this->user])
                ->visible(Features::enabled(Features::passkeys())),
            Tab::make('audit')
                ->label((string) __('user/users.tabs.audit'))
                ->content(Audit::class)
                ->params(['user' => $this->user])
                ->visible(fn (): bool => $this->can('view', [$this->user, 'audit-log'])),
        ];
    }

    protected function rendersWithoutTabs(): bool
    {
        return true;
    }

    public function render(): View
    {
        return view('livewire.pages.internal.controls.user.tabs');
    }
}
