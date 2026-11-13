<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Domain\User\Actions\UpdateLanguage;
use App\Domain\User\Models\User;
use App\Domain\User\Support\Languages;
use App\Livewire\Component;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;

class LanguageSwitch extends Component
{
    #[Locked]
    public string $url = '';

    public function mount(): void
    {
        $this->url = request()->fullUrl();
    }

    public function switch(string $language): void
    {
        abort_unless(Languages::all()->has($language), 404);

        $user = auth()->user();

        if ($user instanceof User) {
            UpdateLanguage::make()->execute($user, $language);
        }

        cookie()->queue(cookie()->forever('language', $language));

        $this->redirect($this->url);
    }

    public function render(): View
    {
        return view('livewire.components.language-switch', [
            'languages' => Languages::all(),
            'current' => app()->getLocale(),
        ]);
    }
}
