<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Policies;

use App\Domain\Shared\Letter\Models\Letter;
use App\Domain\Shared\Letter\Support\LetterType;
use App\Domain\Shared\Letter\Support\LetterTypes;
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
        return $this->createsFor($user, $letter);
    }

    public function update(User $user, Letter $letter): bool
    {
        return $this->allowedBySource($user, $letter, 'update');
    }

    public function delete(User $user, Letter $letter): bool
    {
        return $this->allowedBySource($user, $letter, 'delete');
    }

    private function createsFor(User $user, Letter $letter): bool
    {
        $source = $letter->source;

        if ($source === null) {
            return false;
        }

        $key = $letter->getAttributes()['type'] ?? null;

        if (is_string($key)) {
            return $this->allowedBySource($user, $letter, 'create');
        }

        return collect(LetterTypes::forSource($source))
            ->contains(fn (LetterType $type): bool => $user->can($type->sourceAbility('create'), $source));
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
