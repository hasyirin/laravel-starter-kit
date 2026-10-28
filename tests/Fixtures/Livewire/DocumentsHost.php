<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire;

use App\Livewire\Component;

class DocumentsHost extends Component
{
    /** @var list<array{uuid: string, name: string, size: int, mime_type: string}> */
    public array $documents = [
        ['uuid' => 'contract', 'name' => 'contract.pdf', 'size' => 2048, 'mime_type' => 'application/pdf'],
    ];

    public function render(): string
    {
        return <<<'BLADE'
            <div>
                <x-file-upload label="Documents" wire:model="documents" :rules="['mimes:pdf']" multiple />
            </div>
            BLADE;
    }
}
