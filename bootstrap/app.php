<?php

declare(strict_types=1);

use App\Http\Middleware\AddTraceIdToContext;
use App\Http\Middleware\ResolveUserPreferences;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\LazyCollection;

$domainFolders = function (string $name): Generator {
    $directories = [__DIR__.'/../app/Domain'];

    while ($directories !== []) {
        foreach (glob(array_pop($directories).'/*', GLOB_ONLYDIR) ?: [] as $directory) {
            if (basename($directory) === $name) {
                yield $directory;
            } else {
                $directories[] = $directory;
            }
        }
    }
};

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    // LazyCollection keeps the scan off cached requests and lets event:cache iterate the paths again.
    ->withEvents(discover: LazyCollection::make(function () use ($domainFolders): Generator {
        yield from $domainFolders('Listeners');
    }))
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(AddTraceIdToContext::class);
        $middleware->web(append: [ResolveUserPreferences::class]);
        // app.js writes these from the browser, so they are not encrypted.
        $middleware->encryptCookies(except: ['timezone', 'locale']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

// Scanning only when the console kernel resolves keeps the scan off web requests that never run a command.
$app->afterResolving(ConsoleKernel::class, function (Kernel $kernel) use ($app, $domainFolders): void {
    $app->booted(fn (): Kernel => $kernel->addCommandPaths(iterator_to_array($domainFolders('Commands'), false)));
});

return $app;
