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
use App\Domain\User\Models\UserSession;

class RecordSignInEntry
{
    use Actionable;

    public function execute(
        SignInEvent $event,
        ?SignInResult $result = null,
        ?User $user = null,
        ?SignInMethod $method = null,
        bool $twoFactor = false,
        ?UserSession $session = null,
        ?User $endedBy = null,
    ): SignInEntry {
        $context = AuditLog::context();
        $origin = $session instanceof UserSession
            ? collect($session->only(['impersonator_id', 'ip_address', 'user_agent']))
            : $context;

        return SignInEntry::query()->create([
            'event' => $event,
            'result' => $result,
            'method' => $method,
            'two_factor' => $twoFactor,
            'user_id' => $session->user_id ?? $user?->id,
            'impersonator_id' => $origin->get('impersonator_id'),
            'ended_by_id' => $endedBy?->id,
            'ip_address' => $origin->get('ip_address'),
            'user_agent' => $origin->get('user_agent'),
            'trace_id' => $context->get('trace_id'),
        ]);
    }
}
