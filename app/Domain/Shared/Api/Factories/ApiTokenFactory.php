<?php

declare(strict_types=1);

namespace App\Domain\Shared\Api\Factories;

use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\Shared\Api\Models\ApiToken;
use App\Domain\Shared\Api\Support\ApiTokenFormat;
use Illuminate\Database\Eloquent\Factories\Factory;
use SensitiveParameter;

/**
 * @extends Factory<ApiToken>
 */
class ApiTokenFactory extends Factory
{
    protected $model = ApiToken::class;

    public function definition(): array
    {
        $token = ApiTokenFormat::generate();

        return [
            'api_client_id' => ApiClient::factory(),
            'token_hash' => ApiTokenFormat::hash($token),
            'last_four' => ApiTokenFormat::lastFour($token),
            'scopes' => [],
            'expires_at' => now()->addYear(),
        ];
    }

    public function withPlainToken(#[SensitiveParameter] string $token): static
    {
        return $this->state([
            'token_hash' => ApiTokenFormat::hash($token),
            'last_four' => ApiTokenFormat::lastFour($token),
        ]);
    }

    public function neverExpires(): static
    {
        return $this->state(['expires_at' => null]);
    }

    public function expired(): static
    {
        return $this->state(['expires_at' => now()->subMinute()]);
    }

    public function revoked(): static
    {
        return $this->state(['revoked_at' => now()]);
    }
}
