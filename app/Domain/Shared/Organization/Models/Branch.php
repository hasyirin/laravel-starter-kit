<?php

declare(strict_types=1);

namespace App\Domain\Shared\Organization\Models;

use App\Domain\Shared\Common\Models\Model;
use App\Domain\Shared\Organization\Factories\BranchFactory;
use App\Domain\Shared\Organization\Observers\BranchObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $code
 * @property string $name
 * @property bool $is_owner
 */
#[ObservedBy(BranchObserver::class)]
class Branch extends Model
{
    /** @use HasFactory<BranchFactory> */
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

    protected static function newFactory(): BranchFactory
    {
        return BranchFactory::new();
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public static function owner(): static
    {
        /** @var array<string, mixed> $attributes */
        $attributes = cache()->rememberForever('system.owner.branch', fn (): array => static::unguarded(fn (): static => static::query()->firstOrCreate(['is_owner' => true], [
            'organization_id' => Organization::owner()->id,
            'code' => config()->string('system.owner.branch.code'),
            'name' => config()->string('system.owner.branch.name'),
        ])->refresh())->getAttributes());

        return static::query()->getModel()->newFromBuilder($attributes);
    }

    public static function forgetOwner(): void
    {
        cache()->forget('system.owner.branch');
    }
}
