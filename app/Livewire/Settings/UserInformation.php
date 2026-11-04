<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Domain\User\Actions\UpdateUserInformation;
use App\Domain\User\Concerns\UserInformationValidationRules;
use App\Domain\User\Models\User;
use Flux\Flux;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('User information settings')]
class UserInformation extends Component
{
    use UserInformationValidationRules;

    public string $name = '';

    public string $email = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        /** @var User $user */
        $user = auth()->user();

        $this->name = $user->name;
        $this->email = $user->email;
    }

    /**
     * Update the user information of the currently authenticated user.
     */
    public function save(UpdateUserInformation $updateUserInformation): void
    {
        /** @var User $user */
        $user = auth()->user();

        $this->email = str($this->email)->lower()->value();

        $this->validate($this->userInformationRules($user->id));

        $updateUserInformation->execute($user, $this->name, $this->email);

        Flux::toast(variant: 'success', text: __('user/settings.user_information.updated'));
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
            Flux::toast(variant: 'warning', text: __('user/settings.user_information.verification_throttled', ['seconds' => RateLimiter::availableIn($key)]));

            return;
        }

        Flux::toast(text: __('user/settings.user_information.verification_sent'));
    }

    #[Computed]
    public function unverified(): bool
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
