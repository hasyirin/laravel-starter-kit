<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Admin\Controls\User;

use App\Livewire\Component;
use App\Livewire\Components\Tab;
use App\Livewire\Concerns\InteractsWithTabs;
use App\Livewire\DataTables\Admin\User\RoleDataTable;
use Illuminate\Contracts\View\View;
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
        return [
            Tab::make('roles')
                ->label((string) __('user/users.tabs.roles'))
                ->content(RoleDataTable::class)
                ->params(['userId' => $this->userId]),
        ];
    }

    public function render(): View
    {
        return view('livewire.pages.admin.controls.user.tabs');
    }
}
