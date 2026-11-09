<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire;

class ArchivedLockable extends Lockable
{
    public bool $archived = true;

    public function isDisabled(): bool
    {
        return $this->disabled || $this->archived;
    }
}
