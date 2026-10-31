<?php

declare(strict_types=1);

namespace Tests\Fixtures\Models;

use App\Domain\Shared\Common\Models\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property bool $accepts_notes
 */
class Notebook extends Model
{
    protected $fillable = [
        'accepts_notes',
    ];

    /**
     * @return HasMany<Note, $this>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    protected function casts(): array
    {
        return [
            'accepts_notes' => 'boolean',
        ];
    }
}
