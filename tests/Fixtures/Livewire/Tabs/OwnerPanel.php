<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire\Tabs;

use App\Livewire\Component;

class OwnerPanel extends Component
{
    public string $source = '';

    public string $heading = '';

    public function render(): string
    {
        return '<div>Owner panel {{ $source }} {{ $heading }}</div>';
    }
}
