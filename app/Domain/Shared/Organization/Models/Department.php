<?php

declare(strict_types=1);

namespace App\Domain\Shared\Organization\Models;

use App\Domain\Shared\Common\Models\Model;
use App\Domain\Shared\Organization\Factories\DepartmentFactory;
use App\Domain\Shared\Organization\Observers\DepartmentObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $code
 * @property string $name
 * @property bool $is_owner
 */
#[ObservedBy(DepartmentObserver::class)]
class Department extends Model
{
    /** @use HasFactory<DepartmentFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'code',
        'name',
    ];

    protected $attributes = [
        'is_owner' => false,
    ];

    protected function casts(): array
    {
        return [
            'is_owner' => 'boolean',
        ];
    }

    protected static function newFactory(): DepartmentFactory
    {
        return DepartmentFactory::new();
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return HasMany<Position, $this>
     */
    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    public static function owner(): static
    {
        /** @var array<string, mixed> $attributes */
        $attributes = cache()->rememberForever('system.owner.department', fn (): array => static::unguarded(fn (): static => static::query()->firstOrCreate(['is_owner' => true], [
            'organization_id' => Organization::owner()->id,
            'code' => config()->string('system.owner.department.code'),
            'name' => config()->string('system.owner.department.name'),
        ])->refresh())->getAttributes());

        return static::query()->getModel()->newFromBuilder($attributes);
    }

    public static function forgetOwner(): void
    {
        cache()->forget('system.owner.department');
    }
}
