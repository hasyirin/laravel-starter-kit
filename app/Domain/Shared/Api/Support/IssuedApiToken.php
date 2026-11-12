<?php

declare(strict_types=1);

namespace App\Domain\Shared\Api\Support;

use App\Domain\Shared\Api\Models\ApiToken;
use SensitiveParameter;

final readonly class IssuedApiToken
{
    public function __construct(
        public ApiToken $apiToken,
        #[SensitiveParameter] public string $plainToken,
    ) {}
}
