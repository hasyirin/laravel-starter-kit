<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Domain\Shared\Common\Enums\Feature;
use App\Domain\User\Actions\Settings\UpdateUserInformation;
use App\Domain\User\Concerns\UserInformationValidationRules;
use App\Domain\User\Contracts\Models\MustVerifyPhone;
use App\Domain\User\Models\User;
use App\Http\Enums\Area;
use App\Livewire\Pages\Shared\Page;
use Flux\Flux;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;

class UserInformation extends Page
{
    use UserInformationValidationRules;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        /** @var User $user */
        $user = auth()->user();

        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone ?? '';
    }

    /**
     * Update the user information of the currently authenticated user.
     */
    public function save(): void
    {
        /** @var User $user */
        $user = auth()->user();

        $this->email = str($this->email)->lower()->value();

        $this->validate($this->userInformationRules($user->id, phoneRequired: $this->phoneRequired));

        UpdateUserInformation::make()->execute($user, $this->name, $this->email, $this->phone === '' ? null : $this->phone);

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
            $this->redirectIntended(default: route(Area::current()->route('dashboard'), absolute: false));

            return;
        }

        if (! Feature::EmailVerification->enabled()) {
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

        return Feature::EmailVerification->enabled() && $user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail();
    }

    #[Computed]
    public function phoneRequired(): bool
    {
        /** @var User $user */
        $user = auth()->user();

        return ! $user->checkPermissionTo(config()->string('system.permissions.bypass-authorization'));
    }

    #[Computed]
    public function phoneUnverified(): bool
    {
        $user = auth()->user();

        return Feature::PhoneVerification->enabled() && $user instanceof MustVerifyPhone && $user->getPhoneForVerification() !== null && ! $user->hasVerifiedPhone();
    }

    public function render(): View
    {
        return view('livewire.settings.user-information')->title(__('user/settings.user_information.title'));
    }
}
