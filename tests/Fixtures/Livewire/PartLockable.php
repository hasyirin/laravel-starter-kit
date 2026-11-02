<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire;

class PartLockable extends Lockable
{
    /**
     * @var list<string>
     */
    public array $lockedParts = ['roles'];

    public bool $rolesSaved = false;

    public function isDisabled(?string $part = null): bool
    {
        return $this->disabled || in_array($part, $this->lockedParts, true);
    }

    public function saveRoles(): void
    {
        $this->ensureEnabled('roles');

        $this->rolesSaved = true;
    }

    public function render(): string
    {
        return <<<'BLADE'
            <div>Name {{ $this->isDisabled('name') ? 'locked' : 'open' }}, roles {{ $this->isDisabled('roles') ? 'locked' : 'open' }}</div>
            BLADE;
    }
}
