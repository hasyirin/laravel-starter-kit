<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Domain\User\Actions\DeleteUser;
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
    public function deleteUser(Logout $logout, DeleteUser $deleteUser): void
    {
        $this->validate([
            'password' => $this->currentPasswordRules(),
        ]);

        /** @var User $user */
        $user = auth()->user();

        $logout();
        $deleteUser->execute($user);

        $this->redirect('/', navigate: true);
    }
}
