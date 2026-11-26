<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\User\Actions\Registration\RegisterUser;
use App\Domain\User\Actions\Security\ResetForgottenPassword;
use App\Domain\User\Models\User;
use App\Domain\User\Support\Impersonation;
use App\Domain\User\Support\SignInThrottle;
use App\Domain\User\Support\VerifyPasskey;
use App\Http\Responses\LoginResponse;
use App\Http\Responses\PasskeyLoginResponse;
use App\Http\Responses\TwoFactorLoginResponse;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Laravel\Fortify\Fortify;
use Laravel\Passkeys\Actions\VerifyPasskey as BaseVerifyPasskey;
use Laravel\Passkeys\Contracts\PasskeyLoginResponse as PasskeyLoginResponseContract;
use SensitiveParameter;

class FortifyServiceProvider extends ServiceProvider
{
    private const array OWN_SIGN_IN_METHOD_ROUTES = [
        'two-factor.enable',
        'two-factor.confirm',
        'two-factor.disable',
        'two-factor.qr-code',
        'two-factor.secret-key',
        'two-factor.recovery-codes',
        'two-factor.regenerate-recovery-codes',
        'passkey.registration-options',
        'passkey.store',
        'passkey.destroy',
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(TwoFactorLoginResponseContract::class, TwoFactorLoginResponse::class);
        $this->app->singleton(PasskeyLoginResponseContract::class, PasskeyLoginResponse::class);
        $this->app->bind(BaseVerifyPasskey::class, VerifyPasskey::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
        self::requireVerificationForOwnSignInMethods();
    }

    public static function requireVerificationForOwnSignInMethods(): void
    {
        $verification = ['verified', 'phone.verified'];

        // getByName() cannot find these routes until the app has booted. middleware() only appends.
        foreach (Route::getRoutes()->getRoutes() as $route) {
            if (! in_array($route->getName(), self::OWN_SIGN_IN_METHOD_ROUTES, true)) {
                continue;
            }

            $middleware = collect($route->middleware())->diff($verification);

            $route->action['middleware'] = $middleware->takeUntil('password.confirm')
                ->merge($verification)
                ->merge($middleware->skipUntil('password.confirm'))
                ->all();
        }
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetForgottenPassword::class);
        Fortify::createUsersUsing(RegisterUser::class);
        Fortify::confirmPasswordsUsing(function (User $user, #[SensitiveParameter] ?string $password): bool {
            if (Impersonation::active()) {
                $user = Impersonation::impersonator();
            }

            return $user instanceof User && Hash::check((string) $password, $user->password);
        });
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (): Factory|View => view('livewire.auth.login'));
        Fortify::verifyEmailView(fn (): Factory|View => view('livewire.auth.verify-email'));
        Fortify::twoFactorChallengeView(fn (): Factory|View => view('livewire.auth.two-factor-challenge'));
        Fortify::confirmPasswordView(fn (): Factory|View => view('livewire.auth.confirm-password'));
        Fortify::registerView(fn (): Factory|View => view('livewire.auth.register'));
        Fortify::resetPasswordView(fn (): Factory|View => view('livewire.auth.reset-password'));
        Fortify::requestPasswordResetLinkView(fn (): Factory|View => view('livewire.auth.forgot-password'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', fn (Request $request) => Limit::perMinute(5)
            ->by($request->session()->get('login.id'))
            ->response(fn (Request $request, array $headers): never => SignInThrottle::twoFactor($headers)));

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = $request->string(Fortify::username())
                ->lower()
                ->append('|'.$request->ip())
                ->transliterate()
                ->value();

            return Limit::perMinute(5)
                ->by($throttleKey)
                ->response(fn (Request $request, array $headers): never => SignInThrottle::login($throttleKey, $headers));
        });

        RateLimiter::for('passkeys', function (Request $request) {
            $credentialId = $request->string('credential.id')->value();
            $throttleKey = ($credentialId ?: $request->session()->getId()).'|'.$request->ip();

            return Limit::perMinute(10)
                ->by($throttleKey)
                ->response(fn (Request $request, array $headers): never => SignInThrottle::passkeys($throttleKey, $headers));
        });

        RateLimiter::for('guest-forms', function (Request $request): Limit {
            if (! $request->routeIs('register.store', 'password.email')) {
                return Limit::none();
            }

            return Limit::perMinute(5)->by($request->route()?->getName().'|'.$request->ip());
        });
    }
}
