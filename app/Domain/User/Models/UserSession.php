<?php

declare(strict_types=1);

namespace App\Domain\User\Models;

use App\Domain\Shared\Common\Models\Model;
use App\Domain\User\Factories\UserSessionFactory;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $impersonator_id
 * @property string $session_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property CarbonImmutable $last_activity_at
 * @property CarbonImmutable $signed_in_at
 * @property-read User|null $user
 * @property-read User|null $impersonator
 * @property-read bool $past_maximum
 */
class UserSession extends Model
{
    /** @use HasFactory<UserSessionFactory> */
    use HasFactory;

    public const string SESSION_KEY = 'user_session_id';

    public const int WRITE_INTERVAL_SECONDS = 60;

    protected $fillable = [
        'user_id',
        'impersonator_id',
        'session_id',
        'ip_address',
        'user_agent',
        'last_activity_at',
        'signed_in_at',
    ];

    protected $hidden = [
        'session_id',
    ];

    protected function casts(): array
    {
        return [
            'last_activity_at' => 'datetime',
            'signed_in_at' => 'datetime',
        ];
    }

    protected static function newFactory(): UserSessionFactory
    {
        return UserSessionFactory::new();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function impersonator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'impersonator_id')->withTrashed();
    }

    /**
     * During an impersonation, the session belongs to the impersonator, not to the impersonated user.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function ownedBy(Builder $query, User $user): void
    {
        $query->where(fn (Builder $query): Builder => $query
            ->where('impersonator_id', $user->id)
            ->orWhere(fn (Builder $query): Builder => $query->where('user_id', $user->id)->whereNull('impersonator_id')));
    }

    /**
     * The row writes its last activity at most once a minute, so the idle limit waits one minute more than the session lifetime.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function expired(Builder $query): void
    {
        $idleSince = now()->subSeconds(config()->integer('session.lifetime') * 60 + self::WRITE_INTERVAL_SECONDS);
        $maxDays = self::maxDays();

        $query->where(function (Builder $query) use ($idleSince, $maxDays): void {
            $query->where('last_activity_at', '<', $idleSince);

            if ($maxDays !== null) {
                $query->orWhere('signed_in_at', '<', now()->subDays($maxDays));
            }
        });
    }

    public static function current(): ?self
    {
        $id = session(self::SESSION_KEY);

        if (! is_int($id)) {
            return null;
        }

        return self::query()->find($id);
    }

    public static function maxDays(): ?int
    {
        $days = filter_var(config('system.sessions.max_days'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $days === false ? null : $days;
    }

    protected function shouldLogEvent(string $eventName): bool
    {
        // The sign-in log records the sessions, so their rows stay out of the audit log.
        return false;
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function pastMaximum(): Attribute
    {
        return Attribute::get(function (): bool {
            $maxDays = self::maxDays();

            return $maxDays !== null && $this->signed_in_at->lt(now()->subDays($maxDays));
        });
    }
}
