<?php

declare(strict_types=1);

return [
    // If this key is missing, boost:update runs `php artisan test --list-tests` to set it.
    // That run leaves a Playwright server that holds the output open, so composer update hangs.
    'enforce_tests' => true,
];
