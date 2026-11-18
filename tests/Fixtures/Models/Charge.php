<?php

declare(strict_types=1);

namespace Tests\Fixtures\Models;

use App\Domain\Shared\Common\Models\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string $name
 * @property string|null $billable_type
 * @property int|null $billable_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $billable
 */
class Charge extends Model
{
    protected $fillable = [
        'name',
        'billable_type',
        'billable_id',
    ];

    /**
     * @return MorphTo<\Illuminate\Database\Eloquent\Model, $this>
     */
    public function billable(): MorphTo
    {
        return $this->morphTo();
    }
}
