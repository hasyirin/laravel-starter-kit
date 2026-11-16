<?php

declare(strict_types=1);

namespace App\Domain\Shared\Api\Guards;

use App\Domain\Shared\Api\Enums\ApiCallOutcome;
use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\Shared\Api\Models\ApiToken;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;

class ApiTokenGuard implements Guard
{
    use GuardHelpers;

    private bool $resolved = false;

    private ?ApiToken $apiToken = null;

    private ?ApiCallOutcome $failure = null;

    public function __construct(private Request $request) {}

    public function user(): ?ApiClient
    {
        if (! $this->resolved && $this->user === null) {
            $this->resolved = true;
            $this->user = $this->authenticate();
        }

        if (! $this->user instanceof ApiClient) {
            return null;
        }

        return $this->user;
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function validate(array $credentials = []): bool
    {
        return false;
    }

    public function token(): ?ApiToken
    {
        return $this->apiToken;
    }

    public function failure(): ?ApiCallOutcome
    {
        return $this->failure;
    }

    public function setRequest(Request $request): static
    {
        $this->request = $request;
        $this->resolved = false;
        $this->user = null;
        $this->apiToken = null;
        $this->failure = null;

        return $this;
    }

    private function authenticate(): ?ApiClient
    {
        $token = $this->request->bearerToken();

        if ($token === null || $token === '') {
            $this->failure = ApiCallOutcome::MissingToken;

            return null;
        }

        $apiToken = ApiToken::findByToken($token);
        $apiClient = $apiToken?->apiClient;
        $this->apiToken = $apiToken;

        if (! $apiToken instanceof ApiToken || $apiClient === null) {
            $this->failure = ApiCallOutcome::InvalidToken;

            return null;
        }

        if ($apiToken->revoked) {
            $this->failure = ApiCallOutcome::RevokedToken;

            return null;
        }

        if ($apiToken->expired) {
            $this->failure = ApiCallOutcome::ExpiredToken;

            return null;
        }

        $apiToken->recordUse();

        return $apiClient->withToken($apiToken);
    }
}
