<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\User\Actions\StopImpersonation;
use Illuminate\Http\RedirectResponse;

class StopImpersonationController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        $startUrl = StopImpersonation::make()->execute();

        return redirect($startUrl ?? route('login'));
    }
}
