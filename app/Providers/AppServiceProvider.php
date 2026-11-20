<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Shared\Api\Guards\ApiTokenGuard;
use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\Shared\Common\Enums\Feature;
use App\Domain\Shared\Sms\Channels\SmsChannel;
use App\Domain\Shared\Sms\Contracts\SmsGateway;
use App\Domain\User\Models\User;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\EnsurePhoneIsVerified;
use App\Livewire\Synthesizers\EnumSynth;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Foundation\Application;
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

        $this->configureApiGuard();
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

        Livewire::propertySynthesizer(EnumSynth::class);

        Gate::before(fn (?Authenticatable $user): ?true => $user instanceof User && $user->checkPermissionTo(config('system.permissions.bypass-authorization')) ? true : null);

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
        RateLimiter::for('api-client', function (Request $request): Limit {
            /** @var ApiClient $apiClient */
            $apiClient = $request->user();

            return Limit::perMinute($apiClient->rate_limit_per_minute)->by((string) $apiClient->id);
        });
    }

    protected function configureApiGuard(): void
    {
        auth()->extend('api-token', function (Application $app): ApiTokenGuard {
            $guard = new ApiTokenGuard(request());
            $app->refresh('request', $guard, 'setRequest');

            return $guard;
        });
    }
}
