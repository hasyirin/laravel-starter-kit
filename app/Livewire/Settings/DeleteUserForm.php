<?php

namespace App\Livewire\Settings;

use App\Domain\User\Concerns\PasswordValidationRules;
use App\Domain\User\Models\User;
use App\Livewire\Actions\Logout;
use Livewire\Component;

class DeleteUserForm extends Component
{
    use PasswordValidationRules;

    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => $this->currentPasswordRules(),
        ]);

        /** @var User $user */
        $user = auth()->user();

        tap($user, $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}
