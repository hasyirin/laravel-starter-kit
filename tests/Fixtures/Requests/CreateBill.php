<?php

declare(strict_types=1);

namespace Tests\Fixtures\Requests;

use App\Domain\Shared\Api\Requests\ServiceRequest;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Traits\Body\HasJsonBody;

class CreateBill extends ServiceRequest implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    /**
     * @param  array<string, mixed>  $bill
     */
    public function __construct(private readonly array $bill) {}

    public function resolveEndpoint(): string
    {
        return '/v1/bills';
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return $this->bill;
    }
}
