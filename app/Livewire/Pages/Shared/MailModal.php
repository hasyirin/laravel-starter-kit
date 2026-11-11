<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Shared;

use App\Domain\Shared\Mail\Actions\ResendMail;
use App\Domain\Shared\Mail\Models\Mail;
use App\Livewire\Modal;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;

class MailModal extends Modal
{
    #[Locked]
    public ?Mail $mail = null;

    public string $email = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Mail::class);
    }

    #[On('show')]
    public function show(?int $id = null): void
    {
        abort_if($id === null, 404);

        $mail = Mail::query()->with(['media', 'resendOf'])->findOrFail($id);

        $this->authorize('view', $mail);

        $this->mail = $mail;
        $this->email = (string) $mail->recipient_email;

        parent::show();
    }

    public function resend(): void
    {
        abort_if(! $this->mail instanceof Mail, 404);

        $this->authorize('resend', $this->mail);

        $this->validate(['email' => ['required', 'email:rfc', 'max:255']]);

        ResendMail::make()->execute($this->mail, $this->email);

        Flux::toast(variant: 'success', text: __('mail/mails.resent'));

        $this->close();
    }

    public function render(): View
    {
        return view('livewire.pages.shared.mail-modal');
    }
}
