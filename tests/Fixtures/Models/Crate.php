<?php

declare(strict_types=1);

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $label
 */
class Crate extends Model
{
    use HasUlids;

    protected $fillable = [
        'label',
    ];
}
