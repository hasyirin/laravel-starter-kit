<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Domain\User\Actions\UpdateProfile;
use App\Domain\User\Concerns\ProfileValidationRules;
use App\Domain\User\Models\User;
use App\Domain\User\Support\Preferences;
use DateTimeZone;
use Flux\Flux;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Locale;

#[Title('Profile settings')]
class Profile extends Component
{
    use ProfileValidationRules;

    public string $name = '';

    public string $email = '';

    public string $timezone = '';

    public string $locale = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        /** @var User $user */
        $user = auth()->user();

        $preferences = Preferences::of($user);

        $this->name = $user->name;
        $this->email = $user->email;
        $this->timezone = $preferences->timezone;
        $this->locale = $preferences->locale;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(UpdateProfile $updateProfile): void
    {
        /** @var User $user */
        $user = auth()->user();

        $this->email = str($this->email)->lower()->value();

        $this->validate([
            ...$this->profileRules($user->id),
            'timezone' => ['required', 'timezone:all'],
            'locale' => ['required', Rule::in(array_keys($this->locales))],
        ]);

        $updateProfile->execute($user, $this->name, $this->email, $this->timezone, $this->locale);

        Flux::toast(variant: 'success', text: __('user/settings.profile.updated'));
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $key = 'verification-notification:'.$user->id;

        if (! RateLimiter::attempt($key, 6, $user->sendEmailVerificationNotification(...))) {
            Flux::toast(variant: 'warning', text: __('user/settings.profile.verification_throttled', ['seconds' => RateLimiter::availableIn($key)]));

            return;
        }

        Flux::toast(text: __('user/settings.profile.verification_sent'));
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

        return new Preferences($this->timezone, $this->locale)->datetime(now());
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        $user = auth()->user();

        return $user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        $user = auth()->user();

        return ! $user instanceof MustVerifyEmail || $user->hasVerifiedEmail();
    }
}
