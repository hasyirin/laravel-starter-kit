<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\User\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Blaze\Blaze;
use Livewire\Livewire;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->preventDebugModeInProduction();

        $this->configureDefaults();

        $this->configureRateLimits();
    }

    protected function preventDebugModeInProduction(): void
    {
        if (app()->isProduction() && app()->hasDebugModeEnabled()) {
            // Turn debug mode off first, or this exception renders the debug error page with the stack trace.
            config(['app.debug' => false]);

            throw new RuntimeException('Debug mode must be off in production. Set APP_DEBUG=false.');
        }
    }

    protected function configureDefaults(): void
    {
        URL::forceHttps();

        Date::use(CarbonImmutable::class);

        // The app keeps times in app.timezone, but scheduled tasks run on the clock of the business.
        config(['app.schedule_timezone' => config('system.timezone')]);

        DB::prohibitDestructiveCommands(app()->isProduction());

        Blaze::optimize()->in(resource_path('views/components'));

        Livewire::addPersistentMiddleware([EnsureEmailIsVerified::class]);

        Gate::before(fn (?User $user): ?true => $user?->checkPermissionTo(config('system.permissions.bypass-authorization')) ? true : null);

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->uncompromised()
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
            : null,
        );
    }

    protected function configureRateLimits(): void
    {
        RateLimiter::for('mail-tracking', fn (Request $request): Limit => Limit::perMinute(30)->by($request->ip().'|'.$request->route('token')));
        RateLimiter::for('letter-preview', fn (Request $request): Limit => Limit::perMinute(30)->by((string) $request->user()?->getAuthIdentifier()));
    }
}
