<?php

declare(strict_types=1);

namespace Tests\Fixtures\Models;

use App\Domain\Shared\Common\Models\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * @property int $id
 * @property string $uuid
 * @property string $title
 */
class Survey extends Model
{
    use HasUuids;

    protected $fillable = [
        'title',
    ];

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }
}
