<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire;

use App\Livewire\Pages\Shared\Page;

class StubPage extends Page
{
    public function render(): string
    {
        return '<div></div>';
    }
}
