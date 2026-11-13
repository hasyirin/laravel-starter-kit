<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Shared\Common\Enums\Feature;
use App\Domain\Shared\Sms\Channels\SmsChannel;
use App\Domain\Shared\Sms\Contracts\SmsGateway;
use App\Domain\User\Models\User;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\EnsurePhoneIsVerified;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Blaze\Blaze;
use Livewire\Livewire;
use LogicException;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(function (): SmsGateway {
            $name = config()->string('system.sms.gateway');
            $class = config("system.sms.gateways.{$name}");

            throw_unless(is_string($class), LogicException::class, "The SMS gateway [{$name}] is not in system.sms.gateways.");

            $gateway = resolve($class);

            throw_unless($gateway instanceof SmsGateway, LogicException::class, "system.sms.gateways.{$name} must name a class that implements SmsGateway.");

            return $gateway;
        });

        Notification::resolved(function (ChannelManager $channels): void {
            $channels->extend('sms', fn (): SmsChannel => resolve(SmsChannel::class));
        });
    }

    public function boot(): void
    {
        $this->preventDebugModeInProduction();

        $this->checkFeatures();

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

    protected function checkFeatures(): void
    {
        throw_if(
            Feature::SmsPasswordReset->enabled() && ! Feature::PhoneVerification->enabled(),
            LogicException::class,
            'The SMS password reset needs phone verification. Add Feature::PhoneVerification to system.features.',
        );

        throw_if(
            Feature::SmsPasswordReset->enabled() && ! Feature::PasswordReset->enabled(),
            LogicException::class,
            'The SMS password reset needs the password reset. Add Feature::PasswordReset to system.features.',
        );
    }

    protected function configureDefaults(): void
    {
        URL::forceHttps();

        Date::use(CarbonImmutable::class);

        // The app keeps times in app.timezone, but scheduled tasks run on the clock of the business.
        config(['app.schedule_timezone' => config('system.timezone')]);

        DB::prohibitDestructiveCommands(app()->isProduction());

        Blaze::optimize()->in(resource_path('views/components'));

        Livewire::addPersistentMiddleware([EnsureEmailIsVerified::class, EnsurePhoneIsVerified::class]);

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
