<?php

declare(strict_types=1);

namespace App\Domain\Shared\Api\Actions;

use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\Shared\Common\Concerns\Actions\Actionable;

class SaveApiClient
{
    use Actionable;

    /**
     * @param  array<int, array{address: string, description: string|null}>  $allowedIpAddresses
     */
    public function execute(
        ApiClient $apiClient,
        string $name,
        ?string $description,
        bool $enabled,
        array $allowedIpAddresses,
        bool $allowsAnyIpAddress,
        int $rateLimitPerMinute,
        ?string $service,
    ): ApiClient {
        $apiClient->fill([
            'name' => $name,
            'description' => $description,
            'enabled' => $enabled,
            'allowed_ip_addresses' => $this->allowedIpAddresses($allowedIpAddresses),
            'allows_any_ip_address' => $allowsAnyIpAddress,
            'rate_limit_per_minute' => $rateLimitPerMinute,
            'service' => $service,
        ])->save();

        return $apiClient;
    }

    /**
     * @param  array<int, array{address: string, description: string|null}>  $rows
     * @return list<array{address: string, description: string|null}>
     */
    private function allowedIpAddresses(array $rows): array
    {
        $allowedIpAddresses = collect($rows)
            ->map(fn (array $row): array => [
                'address' => trim($row['address']),
                'description' => str($row['description'])->trim()->value() ?: null,
            ])
            ->reject(fn (array $row): bool => $row['address'] === '')
            ->unique('address')
            ->all();

        return array_values($allowedIpAddresses);
    }
}
