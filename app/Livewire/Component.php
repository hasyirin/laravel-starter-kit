<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithAbilities;

abstract class Component extends \Livewire\Component
{
    use InteractsWithAbilities;
}
