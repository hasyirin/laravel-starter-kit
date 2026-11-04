<?php

declare(strict_types=1);

namespace App\Livewire\Settings\TwoFactor;

use App\Domain\User\Models\User;
use App\Livewire\Concerns\ConfirmsPassword;
use Exception;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Livewire\Attributes\Locked;
use Livewire\Component;

class RecoveryCodes extends Component
{
    use ConfirmsPassword;

    /** @var list<string> */
    #[Locked]
    public array $recoveryCodes = [];

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->load();
    }

    /**
     * Generate new recovery codes for the user.
     */
    public function regenerate(): void
    {
        if (! $this->passwordConfirmed()) {
            return;
        }

        resolve(GenerateNewRecoveryCodes::class)(auth()->user());

        $this->load();
    }

    /**
     * Load the recovery codes for the user.
     */
    private function load(): void
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->hasEnabledTwoFactorAuthentication() && $user->two_factor_recovery_codes) {
            try {
                $this->recoveryCodes = array_values(array_filter($user->recoveryCodes(), is_string(...)));
            } catch (Exception) {
                $this->addError('recoveryCodes', 'Failed to load recovery codes');

                $this->recoveryCodes = [];
            }
        }
    }
}
