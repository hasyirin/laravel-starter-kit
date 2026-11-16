<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\EnsurePhoneIsVerified;
use Illuminate\Pipeline\Pipeline;
use Override;
use Symfony\Component\HttpFoundation\Response;

trait RequiresVerification
{
    private bool $verified = false;

    public function bootRequiresVerification(): void
    {
        $this->ensureVerified();
    }

    // Livewire calls this instead of the boot hooks for the snapshot of a lazy or deferred placeholder.
    #[Override]
    public function skipHydrate(): void
    {
        $this->ensureVerified();

        parent::skipHydrate();
    }

    private function ensureVerified(): void
    {
        if ($this->verified) {
            return;
        }

        $response = resolve(Pipeline::class)
            ->send(request())
            ->through([EnsureEmailIsVerified::class, EnsurePhoneIsVerified::class])
            ->then(function (): Response {
                $this->verified = true;

                return response('');
            });

        if ($this->verified) {
            return;
        }

        abort_unless($response instanceof Response, 403);

        // Livewire runs no redirect of a component that mounts inside an update, so throw the middleware's response.
        abort($response);
    }
}
