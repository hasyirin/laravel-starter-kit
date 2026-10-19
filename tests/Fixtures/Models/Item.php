<?php

declare(strict_types=1);

namespace Tests\Fixtures\Models;

use App\Domain\Shared\Common\Concerns\Models\InteractsWithSortOrder;
use App\Domain\Shared\Common\Models\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property int $id
 * @property string $group
 * @property string $name
 * @property string|null $secret
 * @property int $sort_order
 */
class Item extends Model
{
    use InteractsWithSortOrder;

    protected $fillable = [
        'group',
        'name',
        'secret',
        'sort_order',
    ];

    protected $hidden = [
        'secret',
    ];

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    protected function sortOrderScope(Builder $query): Builder
    {
        return $query->where('group', $this->group);
    }
}
