<?php

declare(strict_types=1);

namespace Tests\Fixtures\Requests;

use App\Domain\Shared\Api\Requests\ServiceRequest;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Traits\Body\HasFormBody;
use SensitiveParameter;

class RequestToken extends ServiceRequest implements HasBody
{
    use HasFormBody;

    protected Method $method = Method::POST;

    public function __construct(#[SensitiveParameter] private readonly string $clientSecret) {}

    public function resolveEndpoint(): string
    {
        return '/oauth/token';
    }

    /**
     * @return array<string, string>
     */
    protected function defaultBody(): array
    {
        return ['grant_type' => 'client_credentials', 'client_secret' => $this->clientSecret];
    }
}
