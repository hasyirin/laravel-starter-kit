<?php

declare(strict_types=1);

namespace App\Domain\Shared\Api\Actions;

use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\Shared\Common\Concerns\Actions\Actionable;

class SaveApiClient
{
    use Actionable;

    /**
     * @param  list<string>  $allowedIpAddresses
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
            'allowed_ip_addresses' => collect($allowedIpAddresses)->map(fn (string $ipAddress): string => trim($ipAddress))->filter()->unique()->values()->all(),
            'allows_any_ip_address' => $allowsAnyIpAddress,
            'rate_limit_per_minute' => $rateLimitPerMinute,
            'service' => $service,
        ])->save();

        return $apiClient;
    }
}
