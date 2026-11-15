<?php

declare(strict_types=1);

namespace Tests\Fixtures\Connectors;

use App\Domain\Shared\Api\Connectors\ServiceConnector;

class BillingConnector extends ServiceConnector
{
    public function service(): string
    {
        return 'billing';
    }
}
