<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Shared\Common\Support\ContextCauserResolver;
use App\Domain\User\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Blaze\Blaze;
use RuntimeException;
use Spatie\Activitylog\Support\CauserResolver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(CauserResolver::class, ContextCauserResolver::class);
    }

    public function boot(): void
    {
        $this->preventDebugModeInProduction();

        $this->configureDefaults();
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

        DB::prohibitDestructiveCommands(app()->isProduction());

        Blaze::optimize()->in(resource_path('views/components'));

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
}
