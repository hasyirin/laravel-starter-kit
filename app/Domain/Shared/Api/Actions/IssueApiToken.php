<?php

declare(strict_types=1);

namespace App\Domain\Shared\Api\Actions;

use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\Shared\Api\Support\ApiTokenFormat;
use App\Domain\Shared\Api\Support\IssuedApiToken;
use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use Illuminate\Validation\ValidationException;
use LogicException;

class IssueApiToken
{
    use Actionable;

    /**
     * @param  array<int, string>  $scopes
     */
    public function execute(ApiClient $apiClient, array $scopes, bool $neverExpires = false): IssuedApiToken
    {
        /** @var list<string> $declared */
        $declared = config()->array('system.api.scopes');

        $unknown = collect($scopes)->diff($declared)->first();
        throw_if($unknown !== null, LogicException::class, "The scope [{$unknown}] is not in system.api.scopes.");

        $max = config()->integer('system.api.max_active_tokens');
        throw_if($apiClient->tokens()->active()->count() >= $max, ValidationException::withMessages(['tokens' => __('api/api-clients.token_limit', ['max' => $max])]));

        $token = ApiTokenFormat::generate();
        $apiToken = $apiClient->tokens()->create([
            'token_hash' => ApiTokenFormat::hash($token),
            'last_four' => ApiTokenFormat::lastFour($token),
            'scopes' => collect($scopes)->unique()->values()->all(),
            'expires_at' => $neverExpires ? null : now()->addDays(config()->integer('system.api.token_lifetime_days')),
        ]);

        return new IssuedApiToken($apiToken, $token);
    }
}
