<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Domain\User\Actions\UpdateProfile;
use App\Domain\User\Concerns\ProfileValidationRules;
use App\Domain\User\Models\User;
use Flux\Flux;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Profile settings')]
class Profile extends Component
{
    use ProfileValidationRules;

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
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(UpdateProfile $updateProfile): void
    {
        /** @var User $user */
        $user = auth()->user();

        $this->validate($this->profileRules($user->id));

        $updateProfile->execute($user, $this->name, $this->email);

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

        $user->sendEmailVerificationNotification();

        Flux::toast(text: __('user/settings.profile.verification_sent'));
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
