<?php

declare(strict_types=1);

use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Blade;

test('Filament scripts run only once across wire:navigate visits', function (): void {
    expect(Blade::render('@filamentScripts'))->toContain('data-navigate-once');
});

test('Filament components use the blue accent of the app', function (): void {
    expect(Blade::render('@filamentStyles'))->toContain('--primary-600:'.Color::Blue[600]);
});
