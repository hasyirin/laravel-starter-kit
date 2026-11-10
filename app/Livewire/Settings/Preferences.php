<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Domain\User\Actions\UpdatePreferences;
use App\Domain\User\Models\User;
use App\Domain\User\Support\Preferences as UserPreferences;
use App\Livewire\Pages\Shared\Page;
use DateTimeZone;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Locale;

#[Title('Preferences')]
class Preferences extends Page
{
    public string $timezone = '';

    public string $locale = '';

    public function mount(): void
    {
        /** @var User $user */
        $user = auth()->user();

        $preferences = UserPreferences::of($user);

        $this->timezone = $preferences->timezone;
        $this->locale = $preferences->locale;
    }

    public function save(): void
    {
        /** @var User $user */
        $user = auth()->user();

        $this->validate([
            'timezone' => ['required', 'timezone:all'],
            'locale' => ['required', Rule::in(array_keys($this->locales))],
        ]);

        UpdatePreferences::make()->execute($user, $this->timezone, $this->locale);

        Flux::toast(variant: 'success', text: __('user/settings.preferences.updated'));
    }

    /**
     * @return array<array<string, string>>
     */
    #[Computed]
    public function timezones(): array
    {
        return collect(DateTimeZone::listIdentifiers())
            ->groupBy(fn (string $timezone): string => str($timezone)->before('/')->value())
            ->map(fn (Collection $timezones): array => $timezones->mapWithKeys(fn (string $timezone): array => [
                $timezone => str($timezone)->after('/')->replace(['_', '/'], [' ', ' / '])->append(' (UTC', now($timezone)->format('P'), ')')->value(),
            ])->all())
            ->all();
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function locales(): array
    {
        return collect(config()->array('system.locales'))
            ->filter(fn (mixed $locale): bool => is_string($locale))
            ->mapWithKeys(fn (string $locale): array => [$locale => (string) Locale::getDisplayName($locale, app()->getLocale())])
            ->all();
    }

    #[Computed]
    public function example(): ?string
    {
        if (! in_array($this->timezone, DateTimeZone::listIdentifiers(), true) || ! array_key_exists($this->locale, $this->locales)) {
            return null;
        }

        return new UserPreferences($this->timezone, $this->locale)->datetime(now());
    }
}
