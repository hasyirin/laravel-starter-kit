<?php

declare(strict_types=1);

namespace Tests\Fixtures\Models;

use App\Domain\Shared\Common\Models\Model;
use Hasyirin\Address\Concerns\InteractsWithAddresses;
use Hasyirin\Address\Contracts\Addressable;

/**
 * @property int $id
 * @property string $name
 */
class Venue extends Model implements Addressable
{
    use InteractsWithAddresses;

    protected $fillable = [
        'name',
    ];
}
