<?php

declare(strict_types=1);

use App\Domain\Shared\Actor\Models\Action;

return [
    'tables' => [
        'actions' => 'actions',
    ],

    'models' => [
        'action' => Action::class,
    ],

    'guard' => null,
];
