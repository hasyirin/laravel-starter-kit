<?php

declare(strict_types=1);

namespace App\Providers;

use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\ServiceProvider;

class FilamentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        FilamentView::spa();

        $this->configureColors();
    }

    private function configureColors(): void
    {
        FilamentColor::register([
            'primary' => Color::Blue,
        ]);
    }
}
