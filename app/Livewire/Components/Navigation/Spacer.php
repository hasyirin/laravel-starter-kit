<?php

declare(strict_types=1);

namespace App\Livewire\Components\Navigation;

use Illuminate\Contracts\Support\Htmlable;

final class Spacer implements Htmlable
{
    public function toHtml(): string
    {
        return view('partials.navigation.spacer')->render();
    }

    public static function make(): self
    {
        return new self;
    }
}
