<?php

declare(strict_types=1);

use Filament\Actions\Exports\Http\Controllers\DownloadExport;
use Filament\Actions\Imports\Http\Controllers\DownloadImportFailureCsv;
use Filament\Support\Colors\Color;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;

test('Filament scripts run only once across wire:navigate visits', function (): void {
    expect(Blade::render('@filamentScripts'))->toContain('data-navigate-once');
});

test('Filament components use the blue accent of the app', function (): void {
    expect(Blade::render('@filamentStyles'))->toContain('--primary-600:'.Color::Blue[600]);
});

test('Filament export and import download URLs give 404 to a guest', function (string $url): void {
    $response = $this->get($url);

    $response->assertNotFound();
})->with([
    'an export with id 1' => ['/filament/exports/1/download'],
    'an export with a malformed id' => ['/filament/exports/abc/download'],
    'an import with id 1' => ['/filament/imports/1/failed-rows/download'],
    'an import with a malformed id' => ['/filament/imports/abc/failed-rows/download'],
]);

test('Filament export and import download controllers have no route', function (): void {
    $controllers = collect(resolve(Router::class)->getRoutes()->getRoutes())
        ->map(fn (Route $route): ?string => $route->getControllerClass());

    expect($controllers)
        ->not->toContain(DownloadExport::class)
        ->not->toContain(DownloadImportFailureCsv::class);
});
