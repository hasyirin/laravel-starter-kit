<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Domain\User\Actions\ConfirmPasswordResetCode;
use App\Domain\User\Actions\SendPasswordResetCode;
use App\Domain\User\Exceptions\SmsCodeThrottled;
use App\Domain\User\Rules\PhoneNumber;
use App\Livewire\Component;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;

class SmsPasswordReset extends Component
{
    public string $phone = '';

    public string $code = '';

    #[Locked]
    public bool $showingCodeStep = false;

    public function send(): void
    {
        $this->validate(['phone' => ['required', 'string', new PhoneNumber]]);

        try {
            SendPasswordResetCode::make()->execute($this->phone, (string) request()->ip());
        } catch (SmsCodeThrottled $smsCodeThrottled) {
            $this->addError('phone', (string) __('auth/sms-password-reset.throttled', ['seconds' => $smsCodeThrottled->seconds]));

            return;
        }

        $this->showingCodeStep = true;
    }

    public function confirm(): void
    {
        $this->validate(['code' => ['required', 'string']]);

        $reset = ConfirmPasswordResetCode::make()->execute($this->phone, $this->code);

        $this->redirect(route('password.reset', ['token' => $reset['token'], 'email' => $reset['email']]));
    }

    public function render(): View
    {
        return view('livewire.auth.sms-password-reset')
            ->layout('layouts.auth')
            ->title(__('auth/sms-password-reset.title'));
    }
}
