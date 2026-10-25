<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithAbilities;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Form extends \Livewire\Form
{
    use AuthorizesRequests;
    use InteractsWithAbilities;
}
