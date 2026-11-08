<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\Organization\Department;

use App\Domain\Shared\Organization\Models\Department;
use App\Domain\User\Models\User;
use App\Livewire\Component;
use App\Livewire\Components\Tab;
use App\Livewire\Concerns\InteractsWithTabs;
use App\Livewire\DataTables\Internal\Organization\UserDataTable;
use App\Livewire\Pages\Internal\Controls\Organization\Audit;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;

class Tabs extends Component
{
    use InteractsWithTabs;

    #[Locked]
    public Department $department;

    /**
     * @return list<Tab>
     */
    public function tabs(): array
    {
        return [
            Tab::make('users')
                ->label((string) __('organization/departments.tabs.users'))
                ->content(UserDataTable::class)
                ->params(['record' => $this->department])
                ->visible(fn (): bool => $this->can('viewAny', User::class)),
            Tab::make('audit')
                ->label((string) __('organization/departments.tabs.audit'))
                ->content(Audit::class)
                ->params(['record' => $this->department])
                ->visible(fn (): bool => $this->can('view', [$this->department, 'audit-log'])),
        ];
    }

    protected function rendersWithoutTabs(): bool
    {
        return true;
    }

    public function render(): View
    {
        return view('livewire.pages.internal.controls.organization.tabs');
    }
}
