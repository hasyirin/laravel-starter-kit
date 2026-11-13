<?php

declare(strict_types=1);

namespace Tests\Fixtures\Requests;

use App\Domain\Shared\Api\Requests\ServiceRequest;
use Saloon\Contracts\Body\HasBody;
use Saloon\Data\MultipartValue;
use Saloon\Enums\Method;
use Saloon\Traits\Body\HasMultipartBody;

class UploadLogo extends ServiceRequest implements HasBody
{
    use HasMultipartBody;

    protected Method $method = Method::POST;

    public function resolveEndpoint(): string
    {
        return '/v1/logo';
    }

    /**
     * @return list<MultipartValue>
     */
    protected function defaultBody(): array
    {
        return [new MultipartValue('logo', 'logo-bytes', 'logo.png')];
    }
}
