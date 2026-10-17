<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\FactoryServiceProvider;
use App\Providers\FortifyServiceProvider;

return [
    AppServiceProvider::class,
    FactoryServiceProvider::class,
    FortifyServiceProvider::class,
];
