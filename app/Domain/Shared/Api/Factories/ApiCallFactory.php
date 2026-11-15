<?php

declare(strict_types=1);

namespace App\Domain\Shared\Api\Factories;

use App\Domain\Shared\Api\Enums\ApiCallDirection;
use App\Domain\Shared\Api\Enums\ApiCallOutcome;
use App\Domain\Shared\Api\Models\ApiCall;
use App\Domain\Shared\Api\Models\ApiClient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApiCall>
 */
class ApiCallFactory extends Factory
{
    protected $model = ApiCall::class;

    public function definition(): array
    {
        return [
            'direction' => ApiCallDirection::Inbound,
            'method' => 'GET',
            'url' => '/api/v1/ping',
            'route_name' => 'api.v1.ping',
            'status' => 200,
            'outcome' => ApiCallOutcome::Ok,
            'duration_ms' => fake()->numberBetween(5, 500),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'trace_id' => fake()->uuid(),
            'request_size' => 0,
            'response_size' => fake()->numberBetween(10, 2_000),
        ];
    }

    public function inbound(): static
    {
        return $this->state(['direction' => ApiCallDirection::Inbound]);
    }

    public function outbound(string $service = 'billing'): static
    {
        return $this->state([
            'direction' => ApiCallDirection::Outbound,
            'service' => $service,
            'url' => "https://{$service}.example.com/v1/status",
            'route_name' => null,
            'ip_address' => null,
            'user_agent' => null,
        ]);
    }

    public function byClient(ApiClient $apiClient): static
    {
        return $this->state([
            'api_client_id' => $apiClient->id,
            'api_client_name' => $apiClient->name,
        ]);
    }

    public function rejected(ApiCallOutcome $outcome = ApiCallOutcome::InvalidToken): static
    {
        return $this->state([
            'outcome' => $outcome,
            'status' => match ($outcome) {
                ApiCallOutcome::MissingToken, ApiCallOutcome::InvalidToken, ApiCallOutcome::ExpiredToken, ApiCallOutcome::RevokedToken => 401,
                ApiCallOutcome::RateLimited => 429,
                ApiCallOutcome::ValidationFailed => 422,
                default => 403,
            },
        ]);
    }

    public function serverError(): static
    {
        return $this->state([
            'status' => 500,
            'outcome' => ApiCallOutcome::ServerError,
        ]);
    }

    public function connectionFailed(): static
    {
        return $this->outbound()->state([
            'status' => null,
            'outcome' => ApiCallOutcome::ConnectionFailed,
            'response_size' => null,
            'error' => 'cURL error 7: Failed to connect.',
        ]);
    }
}
