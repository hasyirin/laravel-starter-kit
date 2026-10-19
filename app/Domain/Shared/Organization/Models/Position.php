<?php

declare(strict_types=1);

namespace App\Domain\Shared\Organization\Models;

use App\Domain\Shared\Common\Models\Model;
use App\Domain\Shared\Organization\Factories\PositionFactory;
use App\Domain\Shared\Organization\Observers\PositionObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $organization_id
 * @property int|null $department_id
 * @property string $name
 */
#[ObservedBy(PositionObserver::class)]
class Position extends Model
{
    /** @use HasFactory<PositionFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'department_id',
        'name',
    ];

    protected static function newFactory(): PositionFactory
    {
        return PositionFactory::new();
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
