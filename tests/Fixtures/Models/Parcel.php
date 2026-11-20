<?php

declare(strict_types=1);

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $label
 */
class Parcel extends Model
{
    use HasUuids;

    protected $fillable = [
        'label',
    ];
}
