<?php

declare(strict_types=1);

namespace App\Domain\Shared\Api\Factories;

use App\Domain\Shared\Api\Models\ApiClient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApiClient>
 */
class ApiClientFactory extends Factory
{
    protected $model = ApiClient::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'description' => fake()->sentence(),
            'enabled' => true,
            'allowed_ip_addresses' => [['address' => '192.0.2.10', 'description' => null]],
            'allows_any_ip_address' => false,
            'rate_limit_per_minute' => 60,
            'service' => null,
        ];
    }

    public function disabled(): static
    {
        return $this->state(['enabled' => false]);
    }

    public function anyIpAddress(): static
    {
        return $this->state(['allows_any_ip_address' => true]);
    }

    public function allowedIpAddresses(string ...$addresses): static
    {
        return $this->state([
            'allowed_ip_addresses' => collect($addresses)
                ->map(fn (string $address): array => ['address' => $address, 'description' => null])
                ->all(),
        ]);
    }
}
