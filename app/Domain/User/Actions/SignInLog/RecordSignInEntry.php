<?php

declare(strict_types=1);

namespace App\Domain\User\Actions\SignInLog;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Common\Support\AuditLog;
use App\Domain\User\Enums\SignInEvent;
use App\Domain\User\Enums\SignInMethod;
use App\Domain\User\Enums\SignInResult;
use App\Domain\User\Models\SignInEntry;
use App\Domain\User\Models\User;

class RecordSignInEntry
{
    use Actionable;

    public function execute(
        SignInEvent $event,
        ?SignInResult $result = null,
        ?User $user = null,
        ?SignInMethod $method = null,
        bool $twoFactor = false,
    ): SignInEntry {
        $context = AuditLog::context();

        return SignInEntry::query()->create([
            'event' => $event,
            'result' => $result,
            'method' => $method,
            'two_factor' => $twoFactor,
            'user_id' => $user?->id,
            'impersonator_id' => $context->get('impersonator_id'),
            'ip_address' => $context->get('ip_address'),
            'user_agent' => $context->get('user_agent'),
            'trace_id' => $context->get('trace_id'),
        ]);
    }
}
