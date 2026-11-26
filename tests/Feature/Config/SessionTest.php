<?php

declare(strict_types=1);

test('a session ends after 30 idle days when the server does not set a lifetime', function (): void {
    $server = $_SERVER;
    $env = $_ENV;
    $putenv = getenv('SESSION_LIFETIME');
    unset($_SERVER['SESSION_LIFETIME'], $_ENV['SESSION_LIFETIME']);
    putenv('SESSION_LIFETIME');

    try {
        $lifetime = (require config_path('session.php'))['lifetime'];
    } finally {
        $_SERVER = $server;
        $_ENV = $env;

        if ($putenv !== false) {
            putenv("SESSION_LIFETIME={$putenv}");
        }
    }

    expect($lifetime)->toBe(43200);
});
