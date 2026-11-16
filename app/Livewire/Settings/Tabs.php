<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Livewire\Component;
use App\Livewire\Components\Tab;
use App\Livewire\Concerns\InteractsWithTabs;
use Illuminate\Contracts\View\View;

class Tabs extends Component
{
    use InteractsWithTabs;

    /**
     * @return list<Tab>
     */
    public function tabs(): array
    {
        return [
            Tab::make('user-information')
                ->label((string) __('user/settings.nav.user_information'))
                ->content(UserInformation::class),
            Tab::make('preferences')
                ->label((string) __('user/settings.nav.preferences'))
                ->content(Preferences::class),
            Tab::make('security')
                ->label((string) __('user/settings.nav.security'))
                ->content(Security::class),
            Tab::make('account')
                ->label((string) __('user/settings.nav.account'))
                ->content(DeleteUserForm::class),
        ];
    }

    public function render(): View
    {
        return view('livewire.settings.tabs');
    }
}
