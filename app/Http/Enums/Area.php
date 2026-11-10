<?php

declare(strict_types=1);

namespace App\Http\Enums;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

enum Area: string
{
    case Guest = 'guest';
    case App = 'app';
    case Internal = 'internal';
    case System = 'system';

    public static function current(): self
    {
        $route = Livewire::isLivewireRequest() ? self::pageRoute() : request()->route();

        if (! $route instanceof Route) {
            return self::Guest;
        }

        return match (true) {
            $route->named('internal.*') => self::Internal,
            $route->named('system.*') => self::System,
            in_array('auth', $route->gatherMiddleware(), true) => self::App,
            default => self::Guest,
        };
    }

    public function route(string $name): string
    {
        return match ($this) {
            self::Internal, self::System => "{$this->value}.{$name}",
            self::Guest, self::App => $name,
        };
    }

    // A Livewire update request has Livewire's own route, so this finds the route of the page that sent it.
    private static function pageRoute(): ?Route
    {
        try {
            return resolve(Router::class)->getRoutes()->match(Request::create(Livewire::originalUrl(), Livewire::originalMethod()));
        } catch (HttpException) {
            return null;
        }
    }
}
