<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Admin\Controls\Role;

use App\Domain\Shared\Permission\Models\Role;
use App\Domain\User\Models\User;
use App\Livewire\Component;
use App\Livewire\Components\Tab;
use App\Livewire\Concerns\InteractsWithTabs;
use App\Livewire\DataTables\Admin\Role\UserDataTable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;

class Tabs extends Component
{
    use InteractsWithTabs;

    #[Locked]
    public Role $role;

    /**
     * @return list<Tab>
     */
    public function tabs(): array
    {
        return [
            Tab::make('users')
                ->label((string) __('permission/roles.tabs.users'))
                ->content(UserDataTable::class)
                ->params(['role' => $this->role])
                ->visible(fn (): bool => $this->can('viewAny', User::class)),
            Tab::make('audit')
                ->label((string) __('permission/roles.tabs.audit'))
                ->content(Audit::class)
                ->params(['role' => $this->role])
                ->visible(fn (): bool => $this->can('view', [$this->role, 'activities'])),
        ];
    }

    public function render(): View
    {
        return view('livewire.pages.admin.controls.role.tabs');
    }

    protected function rendersWithoutTabs(): bool
    {
        return true;
    }
}
