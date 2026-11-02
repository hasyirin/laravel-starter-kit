<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Admin\Controls\User;

use App\Livewire\Component;
use App\Livewire\Components\Tab;
use App\Livewire\Concerns\InteractsWithTabs;
use App\Livewire\DataTables\Admin\User\PasskeyDataTable;
use App\Livewire\DataTables\Admin\User\RoleDataTable;
use Illuminate\Contracts\View\View;
use Laravel\Fortify\Features;
use Livewire\Attributes\Locked;

class Tabs extends Component
{
    use InteractsWithTabs;

    #[Locked]
    public int $userId;

    /**
     * @return list<Tab>
     */
    public function tabs(): array
    {
        $tabs = [
            Tab::make('roles')
                ->label((string) __('user/users.tabs.roles'))
                ->content(RoleDataTable::class)
                ->params(['userId' => $this->userId]),
            Tab::make('permissions')
                ->label((string) __('user/users.tabs.permissions'))
                ->content(Permissions::class)
                ->params(['userId' => $this->userId]),
        ];

        if (Features::canManagePasskeys()) {
            $tabs[] = Tab::make('passkeys')
                ->label((string) __('user/users.tabs.passkeys'))
                ->content(PasskeyDataTable::class)
                ->params(['userId' => $this->userId]);
        }

        return $tabs;
    }

    public function render(): View
    {
        return view('livewire.pages.admin.controls.user.tabs');
    }
}
