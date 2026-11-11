<?php

declare(strict_types=1);

namespace Tests\Fixtures\Models;

use App\Domain\Shared\Common\Concerns\Models\InteractsWithMedia;
use App\Domain\Shared\Common\Contracts\Models\HasMedia;
use App\Domain\Shared\Common\Models\Model;

/**
 * @property int $id
 * @property string $name
 */
class Crest extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'name',
    ];
}
