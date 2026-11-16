<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Domain\Shared\Common\Enums\Feature;
use App\Domain\User\Actions\Security\UpdatePassword;
use App\Domain\User\Concerns\PasswordValidationRules;
use App\Domain\User\Models\User;
use App\Livewire\Component;
use App\Livewire\Concerns\ConfirmsPassword;
use App\Livewire\Concerns\RequiresVerification;
use Exception;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Fortify;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use UnexpectedValueException;

class Security extends Component
{
    use ConfirmsPassword;
    use PasswordValidationRules;
    use RequiresVerification;

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    #[Locked]
    public bool $twoFactorEnabled = false;

    #[Locked]
    public bool $requiresConfirmation = false;

    #[Locked]
    public string $qrCodeSvg = '';

    #[Locked]
    public string $manualSetupKey = '';

    public bool $showingSetupModal = false;

    public bool $showingVerificationStep = false;

    #[Validate('required|string|size:6', onUpdate: false)]
    public string $code = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->ensurePasswordConfirmed();

        if (Feature::TwoFactorAuthentication->enabled()) {
            /** @var User $user */
            $user = auth()->user();

            if (Fortify::confirmsTwoFactorAuthentication() && is_null($user->two_factor_confirmed_at)) {
                resolve(DisableTwoFactorAuthentication::class)($user);
            }

            $this->twoFactorEnabled = $user->hasEnabledTwoFactorAuthentication();
            $this->requiresConfirmation = Feature::TwoFactorAuthentication->optionEnabled('confirm');
        }
    }

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $this->validate([
                'current_password' => $this->currentPasswordRules(),
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $validationException) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $validationException;
        }

        /** @var User $user */
        $user = auth()->user();

        UpdatePassword::make()->execute($user, $this->password);

        $this->reset('current_password', 'password', 'password_confirmation');

        Flux::toast(variant: 'success', text: __('user/settings.password.updated'));
    }

    /**
     * Enable two-factor authentication for the user.
     */
    public function enable(): void
    {
        if (! $this->passwordConfirmed()) {
            return;
        }

        /** @var User $user */
        $user = auth()->user();

        resolve(EnableTwoFactorAuthentication::class)($user);

        if (! $this->requiresConfirmation) {
            $this->twoFactorEnabled = $user->hasEnabledTwoFactorAuthentication();
        }

        $this->loadSetupData();

        $this->showingSetupModal = true;
    }

    /**
     * Load the two-factor authentication setup data for the user.
     */
    private function loadSetupData(): void
    {
        /** @var User $user */
        $user = auth()->user();

        try {
            $manualSetupKey = $user->two_factor_secret === null ? null : decrypt($user->two_factor_secret);

            throw_unless(is_string($manualSetupKey), UnexpectedValueException::class, 'Two-factor authentication has no valid secret.');

            $this->qrCodeSvg = $user->twoFactorQrCodeSvg();
            $this->manualSetupKey = $manualSetupKey;
        } catch (Exception) {
            $this->addError('setupData', __('user/settings.two_factor.setup_failed'));

            $this->reset('qrCodeSvg', 'manualSetupKey');
        }
    }

    /**
     * Show the two-factor verification step if necessary.
     */
    public function showVerificationIfNecessary(): void
    {
        if ($this->requiresConfirmation) {
            $this->showingVerificationStep = true;

            $this->resetErrorBag();

            return;
        }

        $this->closeModal();
    }

    /**
     * Confirm two-factor authentication for the user.
     */
    public function confirmTwoFactor(): void
    {
        if (! $this->passwordConfirmed()) {
            return;
        }

        $this->validate();

        resolve(ConfirmTwoFactorAuthentication::class)(auth()->user(), $this->code);

        $this->closeModal();

        $this->twoFactorEnabled = true;
    }

    /**
     * Reset two-factor verification state.
     */
    public function resetVerification(): void
    {
        $this->reset('code', 'showingVerificationStep');

        $this->resetErrorBag();
    }

    /**
     * Disable two-factor authentication for the user.
     */
    public function disable(): void
    {
        if (! $this->passwordConfirmed()) {
            return;
        }

        resolve(DisableTwoFactorAuthentication::class)(auth()->user());

        $this->twoFactorEnabled = false;
    }

    /**
     * Close the two-factor authentication modal.
     */
    public function closeModal(): void
    {
        $this->reset(
            'code',
            'manualSetupKey',
            'qrCodeSvg',
            'showingSetupModal',
            'showingVerificationStep',
        );

        $this->resetErrorBag();

        if (! $this->requiresConfirmation) {
            /** @var User $user */
            $user = auth()->user();

            $this->twoFactorEnabled = $user->hasEnabledTwoFactorAuthentication();
        }
    }

    /**
     * Get the current modal configuration state.
     *
     * @return array{title: string, description: string, buttonText: string}
     */
    #[Computed]
    public function modalConfig(): array
    {
        if ($this->twoFactorEnabled) {
            return [
                'title' => (string) __('user/settings.two_factor.modal.enabled.title'),
                'description' => (string) __('user/settings.two_factor.modal.enabled.description'),
                'buttonText' => (string) __('common/buttons.close'),
            ];
        }

        if ($this->showingVerificationStep) {
            return [
                'title' => (string) __('user/settings.two_factor.modal.verify.title'),
                'description' => (string) __('user/settings.two_factor.modal.verify.description'),
                'buttonText' => (string) __('common/buttons.continue'),
            ];
        }

        return [
            'title' => (string) __('user/settings.two_factor.modal.setup.title'),
            'description' => (string) __('user/settings.two_factor.modal.setup.description'),
            'buttonText' => (string) __('common/buttons.continue'),
        ];
    }

    public function render(): View
    {
        return view('livewire.settings.security');
    }
}
