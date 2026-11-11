<?php

declare(strict_types=1);

namespace Tests\Fixtures\Models;

use App\Domain\Shared\Common\Models\Model;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 */
class Badge extends Model
{
    public function auditLabel(): string
    {
        return $this->code;
    }
}
