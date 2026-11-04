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
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;

class Tabs extends Component
{
    use InteractsWithTabs;

    #[Locked]
    public int $roleId;

    /**
     * @return list<Tab>
     */
    public function tabs(): array
    {
        return [
            Tab::make('users')
                ->label((string) __('permission/roles.tabs.users'))
                ->content(UserDataTable::class)
                ->params(['roleId' => $this->roleId])
                ->visible(fn (): bool => $this->can('viewAny', User::class)),
            Tab::make('audit')
                ->label((string) __('permission/roles.tabs.audit'))
                ->content(Audit::class)
                ->params(['roleId' => $this->roleId])
                ->visible(fn (): bool => $this->can('view', [$this->role, 'activities'])),
        ];
    }

    #[Computed]
    public function role(): Role
    {
        return Role::query()->findOrFail($this->roleId);
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
