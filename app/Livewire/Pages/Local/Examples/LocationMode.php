<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Local\Examples;

enum LocationMode: string
{
    case Optional = 'optional';
    case Required = 'required';
}
