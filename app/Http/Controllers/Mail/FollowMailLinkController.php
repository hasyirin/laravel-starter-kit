<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mail;

use App\Domain\Shared\Mail\Models\MailLink;
use App\Domain\User\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class FollowMailLinkController extends Controller
{
    public function __invoke(string $token): RedirectResponse
    {
        $link = MailLink::query()->with('mail')->where('token', $token)->firstOrFail();

        if (! $link->requires_auth) {
            $link->recordClick();

            return redirect()->away($link->url);
        }

        $user = auth()->user();

        if (! $user instanceof User) {
            return redirect()->guest(route('login'));
        }

        if ($link->mail->isRecipient($user)) {
            $link->recordView();
        }

        return redirect()->away($link->url);
    }
}
