<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Livewire\Attributes\On;

trait Refreshable
{
    #[On('refresh')]
    public function refresh(): void {}
}
