<?php

declare(strict_types=1);

namespace App\Domain\Shared\Organization\Models;

use App\Domain\Shared\Common\Models\Model;
use App\Domain\Shared\Organization\Factories\OrganizationFactory;
use App\Domain\Shared\Organization\Observers\OrganizationObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property bool $is_owner
 */
#[ObservedBy(OrganizationObserver::class)]
class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
    ];

    protected $attributes = [
        'is_owner' => false,
    ];

    protected static function newFactory(): OrganizationFactory
    {
        return OrganizationFactory::new();
    }

    protected function casts(): array
    {
        return [
            'is_owner' => 'boolean',
        ];
    }

    public static function owner(): static
    {
        /** @var array<string, mixed> $attributes */
        $attributes = cache()->rememberForever('system.owner.organization', fn (): array => static::unguarded(fn (): static => static::query()->firstOrCreate(['is_owner' => true], [
            'code' => config()->string('system.owner.organization.code'),
            'name' => config()->string('system.owner.organization.name'),
        ])->refresh())->getAttributes());

        return static::query()->getModel()->newFromBuilder($attributes);
    }

    public static function forgetOwner(): void
    {
        cache()->forget('system.owner.organization');
    }

    /**
     * @return HasMany<Department, $this>
     */
    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    /**
     * @return HasMany<Branch, $this>
     */
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    /**
     * @return HasMany<Position, $this>
     */
    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }
}
