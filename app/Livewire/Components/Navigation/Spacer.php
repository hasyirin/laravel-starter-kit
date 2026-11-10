<?php

declare(strict_types=1);

namespace App\Livewire\Components\Navigation;

use Illuminate\Contracts\View\View;

final class Spacer
{
    public static function make(): self
    {
        return new self;
    }

    public function render(Style $style): View
    {
        return view("partials.navigation.{$style->value}.spacer");
    }
}
