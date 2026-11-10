<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Shared\Letter\Actions\PrintLetter;
use App\Domain\Shared\Letter\Models\Letter;
use App\Domain\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LetterPreviewController extends Controller
{
    public function __invoke(Request $request, Letter $letter): Response
    {
        /** @var User $user */
        $user = $request->user();

        return PrintLetter::make()->execute($letter, $user, preview: true)->inline();
    }
}
