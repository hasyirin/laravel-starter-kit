<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Shared;

use App\Domain\Shared\Message\Actions\ResendMessage;
use App\Domain\Shared\Message\Models\Message;
use App\Livewire\Modal;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;

class MessageModal extends Modal
{
    #[Locked]
    public ?Message $message = null;

    public string $email = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Message::class);
    }

    #[On('show')]
    public function show(?int $id = null): void
    {
        abort_if($id === null, 404);

        $message = Message::query()->with(['media', 'resendOf'])->findOrFail($id);

        $this->authorize('view', $message);

        $this->message = $message;
        $this->email = (string) $message->recipient_address;

        parent::show();
    }

    public function resend(): void
    {
        abort_if(! $this->message instanceof Message, 404);

        $this->authorize('resend', $this->message);

        $this->validate(['email' => ['required', 'email:rfc', 'max:255']]);

        ResendMessage::make()->execute($this->message, $this->email);

        Flux::toast(variant: 'success', text: __('message/messages.resent'));

        $this->close();
    }

    public function render(): View
    {
        return view('livewire.pages.shared.message-modal');
    }
}
