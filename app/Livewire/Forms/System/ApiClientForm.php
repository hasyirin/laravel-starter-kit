<?php

declare(strict_types=1);

namespace App\Livewire\Forms\System;

use App\Domain\Shared\Api\Actions\SaveApiClient;
use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\Shared\Api\Support\AllowedIpAddress;
use App\Livewire\Form;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;

class ApiClientForm extends Form
{
    #[Locked]
    public ?ApiClient $apiClient = null;

    public string $name = '';

    public ?string $description = null;

    public bool $enabled = true;

    public string $allowed_ip_addresses = '';

    public bool $allows_any_ip_address = false;

    public ?int $rate_limit_per_minute = 60;

    public ?string $service = null;

    public function set(ApiClient $apiClient): static
    {
        $this->apiClient = $apiClient;
        $this->name = $apiClient->name;
        $this->description = $apiClient->description;
        $this->enabled = $apiClient->enabled;
        $this->allowed_ip_addresses = collect($apiClient->allowed_ip_addresses)->implode("\n");
        $this->allows_any_ip_address = $apiClient->allows_any_ip_address;
        $this->rate_limit_per_minute = $apiClient->rate_limit_per_minute;
        $this->service = $apiClient->service;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('api_clients', 'name')->withoutTrashed()->ignore($this->apiClient?->id)],
            'description' => ['nullable', 'string'],
            'enabled' => ['boolean'],
            'allowed_ip_addresses' => ['nullable', 'string', $this->validateAllowedIpAddresses(...)],
            'allows_any_ip_address' => ['boolean'],
            'rate_limit_per_minute' => ['required', 'integer', 'min:1'],
            'service' => [
                'nullable',
                'string',
                Rule::in(collect(config()->array('system.api.services'))->keys()),
                Rule::unique('api_clients', 'service')->withoutTrashed()->ignore($this->apiClient?->id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function services(): array
    {
        $services = collect(config()->array('system.api.services'))->keys();
        $stored = $this->apiClient?->service;

        if ($stored !== null) {
            $services->push($stored);
        }

        return $services
            ->unique()
            ->mapWithKeys(fn (string $service): array => [$service => $service])
            ->all();
    }

    /**
     * @return Collection<int, non-empty-string>
     */
    public function allowedIpAddresses(): Collection
    {
        return str($this->allowed_ip_addresses)
            ->explode("\n")
            ->map(fn (string $line): string => trim($line))
            ->reject(fn (string $line): bool => $line === '')
            ->values();
    }

    /**
     * @param  Closure(string): mixed  $fail
     */
    private function validateAllowedIpAddresses(string $attribute, mixed $value, Closure $fail): void
    {
        $invalid = $this->allowedIpAddresses()
            ->first(fn (string $address): bool => ! AllowedIpAddress::valid($address));

        if ($invalid === null) {
            return;
        }

        $fail((string) __('api/api-clients.invalid_ip_address', ['address' => $invalid]));
    }

    public function save(): ApiClient
    {
        $this->validate();

        return SaveApiClient::make()->execute(
            apiClient: $this->apiClient ?? new ApiClient,
            name: $this->name,
            description: $this->description,
            enabled: $this->enabled,
            allowedIpAddresses: $this->allowedIpAddresses()->all(),
            allowsAnyIpAddress: $this->allows_any_ip_address,
            rateLimitPerMinute: (int) $this->rate_limit_per_minute,
            service: $this->service,
        );
    }
}
