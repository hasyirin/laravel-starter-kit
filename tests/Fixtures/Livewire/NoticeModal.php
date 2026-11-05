<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire;

use App\Livewire\Modal;

class NoticeModal extends Modal
{
    public function fail(): void
    {
        $this->addError('notice', 'Failed.');
    }

    public function render(): string
    {
        return <<<'BLADE'
            <div>
                <flux:modal wire:model="showing">Notice</flux:modal>
            </div>
            BLADE;
    }
}
