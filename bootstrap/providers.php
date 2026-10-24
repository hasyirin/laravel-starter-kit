<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\EloquentServiceProvider;
use App\Providers\FilamentServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\HorizonServiceProvider;

return [
    AppServiceProvider::class,
    EloquentServiceProvider::class,
    FilamentServiceProvider::class,
    FortifyServiceProvider::class,
    HorizonServiceProvider::class,
];
