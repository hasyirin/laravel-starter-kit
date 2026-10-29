<?php

declare(strict_types=1);

namespace Tests\Fixtures\Models;

use App\Domain\Shared\Actor\Concerns\InteractsWithPreparer;
use App\Domain\Shared\Common\Models\Model;
use Hasyirin\Actor\Concerns\InteractsWithActions;

/**
 * @property int $id
 * @property string $number
 */
class Invoice extends Model
{
    use InteractsWithActions;
    use InteractsWithPreparer;

    protected $fillable = [
        'number',
    ];
}
