<?php

declare(strict_types=1);

namespace App\Livewire\Forms\System;

use App\Domain\Shared\Api\Actions\SaveApiClient;
use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\Shared\Api\Support\AllowedIpAddress;
use App\Livewire\Form;
use Closure;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;

class ApiClientForm extends Form
{
    #[Locked]
    public ?ApiClient $apiClient = null;

    public string $name = '';

    public ?string $description = null;

    public bool $enabled = true;

    /** @var array<int, array{address: string, description: string|null}> */
    public array $allowed_ip_addresses = [];

    public bool $allows_any_ip_address = false;

    public ?int $rate_limit_per_minute = 60;

    public ?string $service = null;

    public function set(ApiClient $apiClient): static
    {
        $this->apiClient = $apiClient;
        $this->name = $apiClient->name;
        $this->description = $apiClient->description;
        $this->enabled = $apiClient->enabled;
        $this->allowed_ip_addresses = collect($apiClient->allowed_ip_addresses)
            ->map(fn (array $row): array => ['address' => $row['address'], 'description' => $row['description'] ?? ''])
            ->values()
            ->all();
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
            'allowed_ip_addresses' => ['array'],
            'allowed_ip_addresses.*' => ['array'],
            'allowed_ip_addresses.*.address' => ['bail', 'required', 'string', 'max:255', 'distinct:ignore_case', $this->validateAllowedIpAddress(...)],
            'allowed_ip_addresses.*.description' => ['nullable', 'string', 'max:255'],
            'allows_any_ip_address' => ['boolean'],
            'rate_limit_per_minute' => ['required', 'integer', 'min:1', 'max:100000'],
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
     * @param  Closure(string): mixed  $fail
     */
    private function validateAllowedIpAddress(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || AllowedIpAddress::valid($value)) {
            return;
        }

        $fail((string) __('api/api-clients.invalid_ip_address'));
    }

    public function save(): ApiClient
    {
        $this->allowed_ip_addresses = collect($this->allowed_ip_addresses)
            ->map(function (mixed $row): mixed {
                // @phpstan-ignore function.alreadyNarrowedType (the browser can send a row that is not an array)
                if (! is_array($row)) {
                    return $row;
                }

                $address = data_get($row, 'address');

                if (! is_string($address)) {
                    return [...$row, 'address' => ''];
                }

                return [...$row, 'address' => str($address)->trim()->value()];
            })
            ->values()
            ->all();

        $this->validate();

        return $this->transaction(fn (): ApiClient => SaveApiClient::make()->execute(
            apiClient: $this->apiClient ?? new ApiClient,
            name: $this->name,
            description: $this->description,
            enabled: $this->enabled,
            allowedIpAddresses: $this->allowed_ip_addresses,
            allowsAnyIpAddress: $this->allows_any_ip_address,
            rateLimitPerMinute: (int) $this->rate_limit_per_minute,
            service: $this->service,
        ));
    }
}
