<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Passkeys\Contracts\PasskeyLoginResponse as PasskeyLoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class PasskeyLoginResponse extends LoginResponse implements PasskeyLoginResponseContract
{
    public function toResponse($request): Response
    {
        $redirect = redirect()->intended($this->highestAreaUrl());

        return $request->wantsJson()
            ? new JsonResponse(['redirect' => $redirect->getTargetUrl()], 200)
            : $redirect;
    }
}
