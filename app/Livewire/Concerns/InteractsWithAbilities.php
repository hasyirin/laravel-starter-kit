<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Domain\User\Models\User;
use UnitEnum;

trait InteractsWithAbilities
{
    /**
     * @param  array<string|UnitEnum>|string|UnitEnum  $abilities
     */
    protected function cannotAny(array|string|UnitEnum $abilities, mixed $arguments = []): bool
    {
        return ! $this->canAny($abilities, $arguments);
    }

    /**
     * @param  array<string|UnitEnum>|string|UnitEnum  $abilities
     */
    protected function canAny(array|string|UnitEnum $abilities, mixed $arguments = []): bool
    {
        /** @var User $user */
        $user = auth()->user();

        return $user->canAny($abilities, $arguments);
    }

    /**
     * @param  array<string|UnitEnum>|string|UnitEnum  $abilities
     */
    protected function cannot(array|string|UnitEnum $abilities, mixed $arguments = []): bool
    {
        return ! $this->can($abilities, $arguments);
    }

    /**
     * @param  array<string|UnitEnum>|string|UnitEnum  $abilities
     */
    protected function can(array|string|UnitEnum $abilities, mixed $arguments = []): bool
    {
        /** @var User $user */
        $user = auth()->user();

        return $user->can($abilities, $arguments);
    }
}
