<?php

declare(strict_types=1);

namespace Tests\Fixtures\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;

class ShoutText
{
    use Actionable;

    public function execute(string $text): string
    {
        return str($text)->upper()->value();
    }
}
