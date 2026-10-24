<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

test('Filament scripts run only once across wire:navigate visits', function (): void {
    expect(Blade::render('@filamentScripts'))->toContain('data-navigate-once');
});
