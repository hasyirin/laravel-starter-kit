<?php

declare(strict_types=1);

namespace Tests\Fixtures\Requests;

use App\Domain\Shared\Api\Requests\ServiceRequest;
use Saloon\Enums\Method;

class GetStatus extends ServiceRequest
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/v1/status';
    }
}
