<?php

namespace App\Providers;

use App\Domain\User\Actions\Fortify\CreateNewUser;
use App\Domain\User\Actions\Fortify\ResetUserPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
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
        RateLimiter::for('two-factor', fn (Request $request) => Limit::perMinute(5)->by($request->session()->get('login.id')));

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = $request->string(Fortify::username())
                ->lower()
                ->append('|'.$request->ip())
                ->transliterate()
                ->value();

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('passkeys', function (Request $request) {
            $credentialId = $request->string('credential.id')->value();

            return Limit::perMinute(10)->by(
                ($credentialId ?: $request->session()->getId()).'|'.$request->ip(),
            );
        });
    }
}
