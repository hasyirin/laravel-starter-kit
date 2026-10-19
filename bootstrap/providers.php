<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\EloquentServiceProvider;
use App\Providers\FortifyServiceProvider;

return [
    AppServiceProvider::class,
    EloquentServiceProvider::class,
    FortifyServiceProvider::class,
];
