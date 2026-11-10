<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire;

use App\Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.guest')]
class LetterDataFieldsPage extends Component
{
    /** @var array<string, mixed> */
    public array $data = [
        'items' => ['a', 'b', 'c'],
        'lines' => [['item' => 'Pen']],
    ];

    public function render(): string
    {
        return <<<'BLADE'
            @php
                $fields = [
                    \App\Domain\Shared\Letter\Support\Field::list('items'),
                    \App\Domain\Shared\Letter\Support\Field::table('lines', [\App\Domain\Shared\Letter\Support\Field::text('item')]),
                ];
            @endphp
            <div>
                <x-letter.data-fields :$fields prefix="data" :values="$data" />
            </div>
            BLADE;
    }
}
