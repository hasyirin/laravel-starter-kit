<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Domain\User\Actions\Security\ConfirmPasswordResetCode;
use App\Domain\User\Actions\Security\SendPasswordResetCode;
use App\Domain\User\Exceptions\SmsCodeThrottled;
use App\Domain\User\Exceptions\SmsPasswordResetLocked;
use App\Domain\User\Rules\PhoneNumber;
use App\Livewire\Component;
use Carbon\CarbonInterface;
use Carbon\CarbonInterval;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;

class SmsPasswordReset extends Component
{
    public string $phone = '';

    public string $code = '';

    #[Locked]
    public bool $showingCodeStep = false;

    #[Locked]
    public string $notice = '';

    public function send(): void
    {
        $this->reset('notice');
        $this->validate(['phone' => ['required', 'string', new PhoneNumber]]);

        try {
            SendPasswordResetCode::make()->execute($this->phone, (string) request()->ip());
        } catch (SmsCodeThrottled $smsCodeThrottled) {
            $this->addError('phone', (string) __('auth/sms-password-reset.throttled', ['time' => $this->waitTime($smsCodeThrottled->seconds)]));

            return;
        } catch (SmsPasswordResetLocked $smsPasswordResetLocked) {
            $this->addError('phone', $this->lockedMessage($smsPasswordResetLocked));

            return;
        }

        if ($this->showingCodeStep) {
            $this->notice = (string) __('auth/sms-password-reset.resent');
        }

        $this->showingCodeStep = true;
    }

    public function confirm(): void
    {
        $this->validate([
            'phone' => ['required', 'string', new PhoneNumber],
            'code' => ['required', 'string'],
        ]);

        try {
            $reset = ConfirmPasswordResetCode::make()->execute($this->phone, $this->code);
        } catch (SmsPasswordResetLocked $smsPasswordResetLocked) {
            $this->addError('code', $this->lockedMessage($smsPasswordResetLocked));

            return;
        }

        $this->redirect(route('password.reset', ['token' => $reset['token'], 'email' => $reset['email']]));
    }

    public function changeNumber(): void
    {
        $this->reset('code', 'showingCodeStep');
        $this->resetErrorBag();
    }

    private function lockedMessage(SmsPasswordResetLocked $smsPasswordResetLocked): string
    {
        return (string) __('auth/sms-password-reset.locked', ['time' => $this->waitTime($smsPasswordResetLocked->seconds)]);
    }

    private function waitTime(int $seconds): string
    {
        return CarbonInterval::seconds($seconds)
            ->cascade()
            ->forHumans(['parts' => 1, 'options' => CarbonInterface::CEIL]);
    }

    public function render(): View
    {
        return view('livewire.auth.sms-password-reset')
            ->layout('layouts.auth')
            ->title(__('auth/sms-password-reset.title'));
    }
}
