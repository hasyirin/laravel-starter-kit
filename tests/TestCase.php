<?php

declare(strict_types=1);

namespace Tests;

use App\Domain\Shared\Common\Enums\Feature;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Route;
use LogicException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        view()->addNamespace('tests', base_path('tests/Fixtures/Views'));
    }

    protected function skipUnlessFeature(Feature $feature): void
    {
        if (! $feature->enabled()) {
            $this->markTestSkipped("The [{$feature->value}] feature is off in system.features.");
        }
    }

    protected function skipIfFeature(Feature $feature): void
    {
        if ($feature->enabled()) {
            $this->markTestSkipped("The [{$feature->value}] feature is on in system.features.");
        }
    }

    protected function enableFeature(Feature $feature): void
    {
        throw_unless($feature->belongsToFortify(), LogicException::class, "enableFeature() reloads only the Fortify routes, so it cannot turn on [{$feature->value}].");

        config()->push('system.features', $feature);
        Feature::syncFortify();

        Route::group([
            'domain' => config('fortify.domain'),
            'prefix' => config('fortify.prefix'),
        ], base_path('vendor/laravel/fortify/routes/routes.php'));

        Route::getRoutes()->refreshNameLookups();
    }

    protected function disableFeature(Feature ...$features): void
    {
        $remaining = collect(config()->array('system.features'))
            ->reject(fn (mixed $feature): bool => in_array($feature, $features, true))
            ->values()
            ->all();

        config(['system.features' => $remaining]);
        Feature::syncFortify();
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function configureFeature(Feature $feature, array $options): void
    {
        config(["system.feature_options.{$feature->value}" => $options]);
        Feature::syncFortify();
    }
}
