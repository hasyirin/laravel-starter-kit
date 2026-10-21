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
use Spatie\Activitylog\Support\CauserResolver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(CauserResolver::class, ContextCauserResolver::class);
    }

    public function boot(): void
    {
        $this->configureDefaults();
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
