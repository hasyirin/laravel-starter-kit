<?php

declare(strict_types=1);

use App\Http\Middleware\AddTraceIdToContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\LazyCollection;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    // LazyCollection keeps the scan off cached requests and lets event:cache iterate the paths again.
    ->withEvents(discover: LazyCollection::make(function (): Generator {
        $directories = [__DIR__.'/../app/Domain'];

        while ($directories !== []) {
            foreach (glob(array_pop($directories).'/*', GLOB_ONLYDIR) ?: [] as $directory) {
                if (basename($directory) === 'Listeners') {
                    yield $directory;
                } else {
                    $directories[] = $directory;
                }
            }
        }
    }))
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(AddTraceIdToContext::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
