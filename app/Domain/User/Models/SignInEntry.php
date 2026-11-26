<?php

declare(strict_types=1);

namespace App\Domain\User\Models;

use App\Domain\Shared\Common\Models\Model;
use App\Domain\User\Enums\SignInEvent;
use App\Domain\User\Enums\SignInMethod;
use App\Domain\User\Enums\SignInResult;
use App\Domain\User\Factories\SignInEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property SignInEvent $event
 * @property SignInResult|null $result
 * @property SignInMethod|null $method
 * @property bool $two_factor
 * @property int|null $user_id
 * @property int|null $impersonator_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $trace_id
 * @property-read User|null $user
 * @property-read User|null $impersonator
 */
class SignInEntry extends Model
{
    /** @use HasFactory<SignInEntryFactory> */
    use HasFactory;

    protected $fillable = [
        'event',
        'result',
        'method',
        'two_factor',
        'user_id',
        'impersonator_id',
        'ip_address',
        'user_agent',
        'trace_id',
    ];

    protected function casts(): array
    {
        return [
            'event' => SignInEvent::class,
            'result' => SignInResult::class,
            'method' => SignInMethod::class,
            'two_factor' => 'boolean',
        ];
    }

    protected static function newFactory(): SignInEntryFactory
    {
        return SignInEntryFactory::new();
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

    protected function shouldLogEvent(string $eventName): bool
    {
        // The sign-in log is a log of its own, so its rows stay out of the audit log.
        return false;
    }
}
