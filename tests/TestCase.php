<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Saloon\Config;

abstract class TestCase extends BaseTestCase
{
    private static bool $strayRequestsPrevented = false;

    protected function setUp(): void
    {
        parent::setUp();

        view()->addNamespace('tests', base_path('tests/Fixtures/Views'));

        if (! self::$strayRequestsPrevented) {
            Config::preventStrayRequests();
            self::$strayRequestsPrevented = true;
        }
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }

    protected function enableFortifyFeature(string $feature): void
    {
        config()->push('fortify.features', $feature);

        Route::group([
            'domain' => config('fortify.domain'),
            'prefix' => config('fortify.prefix'),
        ], base_path('vendor/laravel/fortify/routes/routes.php'));

        Route::getRoutes()->refreshNameLookups();
    }
}
