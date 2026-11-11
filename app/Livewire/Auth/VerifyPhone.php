<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Domain\Shared\Sms\Support\CallingCodes;
use App\Domain\User\Actions\ConfirmPhone;
use App\Domain\User\Actions\SendPhoneVerificationCode;
use App\Domain\User\Exceptions\SmsCodeThrottled;
use App\Domain\User\Models\User;
use App\Domain\User\Support\Impersonation;
use App\Domain\User\Support\SmsCode;
use App\Livewire\Component;
use Carbon\CarbonInterface;
use Carbon\CarbonInterval;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;

class VerifyPhone extends Component
{
    public string $code = '';

    #[Locked]
    public string $notice = '';

    public function mount(): void
    {
        $user = $this->user();

        if ($user->hasVerifiedPhone()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $phone = $user->phone;

        if ($phone === null || ! CallingCodes::allows($phone)) {
            return;
        }

        if (Impersonation::active() || SmsCode::forVerification($phone)->unused()) {
            return;
        }

        $this->deliver();
    }

    public function send(): void
    {
        if ($this->deliver()) {
            $this->notice = (string) __('auth/verify-phone.sent');
        }
    }

    public function confirm(): void
    {
        $this->validate(['code' => ['required', 'string']]);

        ConfirmPhone::make()->execute($this->user(), $this->code);

        Flux::toast(variant: 'success', text: __('auth/verify-phone.verified'));

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }

    #[Computed]
    public function covered(): bool
    {
        $phone = $this->user()->phone;

        return $phone !== null && CallingCodes::allows($phone);
    }

    private function deliver(): bool
    {
        if (! $this->covered) {
            return false;
        }

        try {
            SendPhoneVerificationCode::make()->execute($this->user());
        } catch (SmsCodeThrottled $smsCodeThrottled) {
            $this->notice = (string) __('auth/verify-phone.throttled', ['time' => $this->waitTime($smsCodeThrottled->seconds)]);

            return false;
        }

        return true;
    }

    private function user(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }

    private function waitTime(int $seconds): string
    {
        return CarbonInterval::seconds($seconds)
            ->cascade()
            ->forHumans(['parts' => 1, 'options' => CarbonInterface::CEIL]);
    }

    public function render(): View
    {
        return view('livewire.auth.verify-phone')
            ->layout('layouts.auth')
            ->title(__('auth/verify-phone.title'));
    }
}
