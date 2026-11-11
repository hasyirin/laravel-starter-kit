<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mail;

use App\Domain\Shared\Message\Models\Message;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class OpenMailController extends Controller
{
    private const string PIXEL = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

    public function __invoke(string $token): Response
    {
        Message::query()->where('tracking_token', $token)->where('tracked', true)->first()?->recordOpen();

        return response(base64_decode(self::PIXEL), 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }
}
