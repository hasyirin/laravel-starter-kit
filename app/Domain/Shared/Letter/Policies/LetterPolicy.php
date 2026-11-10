<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Policies;

use App\Domain\Shared\Letter\Models\Letter;
use App\Domain\User\Models\User;

class LetterPolicy
{
    public function view(User $user, Letter $letter): bool
    {
        return $this->allowedBySource($user, $letter, 'view');
    }

    public function print(User $user, Letter $letter): bool
    {
        return $this->allowedBySource($user, $letter, 'print');
    }

    public function create(User $user, Letter $letter): bool
    {
        return $this->allowedBySource($user, $letter, 'create');
    }

    public function update(User $user, Letter $letter): bool
    {
        return $this->allowedBySource($user, $letter, 'update');
    }

    public function delete(User $user, Letter $letter): bool
    {
        return $this->allowedBySource($user, $letter, 'delete');
    }

    private function allowedBySource(User $user, Letter $letter, string $ability): bool
    {
        $source = $letter->source;

        if ($source === null) {
            return false;
        }

        return $user->can($letter->letter_type->sourceAbility($ability), $source);
    }
}
