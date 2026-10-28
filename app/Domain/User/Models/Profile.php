<?php

declare(strict_types=1);

namespace App\Domain\User\Models;

use App\Domain\Shared\Common\Models\Model;
use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Organization\Models\Position;
use App\Domain\Shared\Permission\Concerns\HasFrozenRoles;
use App\Domain\User\Factories\ProfileFactory;
use App\Domain\User\Observers\ProfileObserver;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * @property int $id
 * @property int|null $organization_id
 * @property int|null $department_id
 * @property int|null $position_id
 * @property int|null $branch_id
 * @property int $user_id
 * @property-read CarbonImmutable|null $ended_at
 * @property-read User $user
 */
#[ObservedBy(ProfileObserver::class)]
class Profile extends Model
{
    /** @use HasFactory<ProfileFactory> */
    use HasFactory;

    use HasFrozenRoles;

    protected string $guard_name = 'web';

    protected $fillable = [
        'organization_id',
        'department_id',
        'position_id',
        'branch_id',
        'user_id',
    ];

    protected static function newFactory(): ProfileFactory
    {
        return ProfileFactory::new();
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

    /**
     * @return BelongsTo<Position, $this>
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * @return Attribute<string, never>
     */
    protected function name(): Attribute
    {
        return Attribute::get(fn (): string => $this->user->name);
    }

    /**
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function withEndedAt(Builder $query): void
    {
        $query->addSelect([
            'ended_at' => $this->newModelQuery()
                ->from($this->getTable(), 'successors')
                ->select('successors.created_at')
                ->whereColumn('successors.user_id', $query->qualifyColumn('user_id'))
                ->whereColumn('successors.id', '>', $query->qualifyColumn('id'))
                ->orderBy('successors.id')
                ->limit(1),
        ])->withCasts(['ended_at' => 'datetime']);
    }

    public function delete(): never
    {
        throw new LogicException('Profiles cannot be deleted.');
    }
}
