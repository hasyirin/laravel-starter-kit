<?php

declare(strict_types=1);

namespace App\Providers;

use App\Livewire\Components\DatabaseNotifications;
use App\Livewire\Components\Notifications;
use Filament\Actions\Exports\Http\Controllers\DownloadExport;
use Filament\Actions\Imports\Http\Controllers\DownloadImportFailureCsv;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\Facades\FilamentView;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class FilamentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        FilamentView::spa();

        DatabaseNotifications::trigger('layouts.partials.notification-bell');
        Livewire::component('database-notifications', DatabaseNotifications::class);
        Livewire::component('notifications', Notifications::class);

        $this->configureColors();
        $this->removeExportAndImportRoutes();
    }

    private function configureColors(): void
    {
        FilamentColor::register([
            'primary' => Color::Blue,
        ]);
    }

    /**
     * The kit has no tables for Filament's exports and imports, so these routes can only fail. An app that uses them deletes this method and publishes Filament's migrations.
     */
    private function removeExportAndImportRoutes(): void
    {
        $router = resolve(Router::class);
        $controllers = [DownloadExport::class, DownloadImportFailureCsv::class];
        $kept = new RouteCollection;

        foreach ($router->getRoutes()->getRoutes() as $route) {
            if (! in_array($route->getControllerClass(), $controllers, true)) {
                $kept->add($route);
            }
        }

        $router->setRoutes($kept);
    }
}
