<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use LogicException;
use Throwable;

class FactoryServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Factory::guessFactoryNamesUsing($this->guessFactoryName(...));

        Factory::guessModelNamesUsing($this->guessModelName(...));
    }

    /**
     * @return class-string<Factory<Model>>
     *
     * @throws Throwable
     */
    private function guessFactoryName(string $model): string
    {
        $factory = str($model)->replaceLast('\\Models\\', '\\Factories\\')->append('Factory')->value();

        throw_unless(is_a($factory, Factory::class, true), LogicException::class, "Factory [{$factory}] does not exist.");

        return $factory;
    }

    /**
     * @param  Factory<Model>  $factory
     * @return class-string<Model>
     *
     * @throws Throwable
     */
    private function guessModelName(Factory $factory): string
    {
        $model = str($factory::class)->replaceLast('\\Factories\\', '\\Models\\')->replaceLast('Factory', '')->value();

        throw_unless(is_a($model, Model::class, true), LogicException::class, "Model [{$model}] does not exist.");

        return $model;
    }
}
