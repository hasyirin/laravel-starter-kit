<?php

declare(strict_types=1);

namespace Tests\Fixtures\Models;

use App\Domain\Shared\Common\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $notebook_id
 * @property string $title
 * @property bool $hidden
 * @property bool $locked
 * @property Notebook $notebook
 */
class Note extends Model
{
    protected $fillable = [
        'notebook_id',
        'title',
        'hidden',
        'locked',
    ];

    /**
     * @return BelongsTo<Notebook, $this>
     */
    public function notebook(): BelongsTo
    {
        return $this->belongsTo(Notebook::class);
    }

    protected function casts(): array
    {
        return [
            'hidden' => 'boolean',
            'locked' => 'boolean',
        ];
    }
}
