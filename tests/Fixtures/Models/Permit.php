<?php

declare(strict_types=1);

namespace Tests\Fixtures\Models;

use App\Domain\Shared\Common\Models\Model;
use App\Domain\Shared\Letter\Concerns\HasLetters;

/**
 * @property int $id
 * @property string $name
 * @property bool $hidden
 * @property bool $locked
 */
class Permit extends Model
{
    use HasLetters;

    protected $fillable = [
        'name',
        'hidden',
        'locked',
    ];

    protected function casts(): array
    {
        return [
            'hidden' => 'boolean',
            'locked' => 'boolean',
        ];
    }
}
