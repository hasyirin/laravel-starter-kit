<?php

declare(strict_types=1);

namespace App\Domain\Shared\Message\Models;

use App\Domain\Shared\Actor\Concerns\InteractsWithPreparer;
use App\Domain\Shared\Actor\Enums\Act;
use App\Domain\Shared\Common\Concerns\Models\InteractsWithMedia;
use App\Domain\Shared\Common\Contracts\Models\HasMedia;
use App\Domain\Shared\Common\Models\Model;
use App\Domain\Shared\Mail\Models\MailLink;
use App\Domain\Shared\Message\Enums\MessageFailureReason;
use App\Domain\Shared\Message\Enums\MessageStatus;
use App\Domain\Shared\Message\Factories\MessageFactory;
use App\Domain\Shared\Message\Observers\MessageObserver;
use App\Domain\Shared\Message\Support\SensitiveValues;
use App\Domain\User\Models\User;
use Carbon\CarbonImmutable;
use Hasyirin\Actor\Facades\Actor;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Notifications\Notification;
use Throwable;

/**
 * @property int $id
 * @property string $channel
 * @property string|null $notification_id
 * @property string|null $notification_type
 * @property string|null $payload
 * @property string|null $notifiable_type
 * @property int|null $notifiable_id
 * @property string|null $resource_type
 * @property int|null $resource_id
 * @property string|null $recipient_address
 * @property string|null $recipient_name
 * @property list<array{address: string, name: string}>|null $cc
 * @property list<array{address: string, name: string}>|null $bcc
 * @property string|null $intercepted_to
 * @property string|null $subject
 * @property string|null $html_body
 * @property string|null $text_body
 * @property string|null $provider_id
 * @property MessageStatus $status
 * @property array{type: string, code: int|string, message: string}|null $error
 * @property bool $tracked
 * @property string $tracking_token
 * @property int $open_count
 * @property CarbonImmutable|null $opened_at
 * @property CarbonImmutable|null $clicked_at
 * @property CarbonImmutable|null $viewed_at
 * @property CarbonImmutable|null $sent_at
 * @property CarbonImmutable|null $failed_at
 * @property int|null $resend_of_id
 * @property-read Notification|null $notification
 * @property-read int|null $resends_count
 * @property-read string $channel_label
 * @property-read string|null $content
 * @property-read MessageFailureReason|null $failure_reason
 * @property-read bool $resendable
 * @property-read bool $stuck
 */
#[ObservedBy(MessageObserver::class)]
class Message extends Model implements HasMedia
{
    /** @use HasFactory<MessageFactory> */
    use HasFactory;

    use InteractsWithMedia;
    use InteractsWithPreparer;

    /**
     * Longer than every try and backoff of a queued email, so a row this old lost its job.
     */
    public const int STUCK_AFTER_MINUTES = 60;

    protected $fillable = [
        'channel',
        'notification_id',
        'notification_type',
        'payload',
        'notifiable_type',
        'notifiable_id',
        'resource_type',
        'resource_id',
        'recipient_address',
        'recipient_name',
        'cc',
        'bcc',
        'intercepted_to',
        'subject',
        'html_body',
        'text_body',
        'provider_id',
        'status',
        'error',
        'sent_at',
        'failed_at',
        'resend_of_id',
    ];

    protected $hidden = [
        'payload',
        'tracking_token',
    ];

    protected $attributes = [
        'status' => MessageStatus::Sending,
        'tracked' => false,
        'open_count' => 0,
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'encrypted',
            'cc' => 'array',
            'bcc' => 'array',
            'status' => MessageStatus::class,
            'error' => 'array',
            'tracked' => 'boolean',
            'open_count' => 'integer',
            'opened_at' => 'datetime',
            'clicked_at' => 'datetime',
            'viewed_at' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    protected static function newFactory(): MessageFactory
    {
        return MessageFactory::new();
    }

    /**
     * @return MorphTo<\Illuminate\Database\Eloquent\Model, $this>
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return MorphTo<\Illuminate\Database\Eloquent\Model, $this>
     */
    public function resource(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasMany<MailLink, $this>
     */
    public function links(): HasMany
    {
        return $this->hasMany(MailLink::class);
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function resendOf(): BelongsTo
    {
        return $this->belongsTo(self::class);
    }

    /**
     * @return HasMany<self, $this>
     */
    public function resends(): HasMany
    {
        return $this->hasMany(self::class, 'resend_of_id')->oldest('id');
    }

    protected function shouldLogEvent(string $eventName): bool
    {
        // The message log is a log of its own, so its rows stay out of the audit log.
        return false;
    }

    public function markAsSent(?string $providerId): void
    {
        $this->update([
            'status' => MessageStatus::Sent,
            'provider_id' => $providerId,
            'sent_at' => now(),
        ]);
    }

    public function markAsFailed(Throwable $exception, ?SensitiveValues $sensitive = null): void
    {
        $this->fail($this->describe($exception, $sensitive));
    }

    public function recordError(Throwable $exception, ?SensitiveValues $sensitive = null): void
    {
        $this->update(['error' => $this->describe($exception, $sensitive)]);
    }

    /**
     * The last try already recorded its error with the sensitive values hidden. The exception of the job cannot hide them.
     */
    public function failAfterLastTry(Throwable $exception): void
    {
        $this->fail($this->error ?? $this->describe($exception, null));
    }

    public function failBecause(MessageFailureReason $reason, string $text): void
    {
        $this->fail(['type' => $reason->value, 'code' => 0, 'message' => $text]);
    }

    /**
     * @return array{type: string, code: int|string, message: string}
     */
    private function describe(Throwable $exception, ?SensitiveValues $sensitive): array
    {
        $text = $exception->getMessage();

        return ['type' => $exception::class, 'code' => $exception->getCode(), 'message' => $sensitive?->mask($text) ?? $text];
    }

    /**
     * @param  array{type: string, code: int|string, message: string}  $error
     */
    public function fail(array $error): void
    {
        $this->update(['status' => MessageStatus::Failed, 'error' => $error, 'failed_at' => now()]);
    }

    public function recordPreparerFromContext(): void
    {
        if (auth(config('actor.guard'))->check()) {
            return;
        }

        $user = User::withTrashed()->whereKey(context()->get('user_id'))->first();

        if ($user !== null) {
            Actor::act($this, Act::Prepare, $user);
        }
    }

    public function recordOpen(): void
    {
        $this->increment('open_count', 1, ['opened_at' => $this->opened_at ?? now()]);
    }

    public function isRecipient(User $user): bool
    {
        if ($this->notifiable_type === $user->getMorphClass() && $this->notifiable_id === $user->getKey()) {
            return true;
        }

        return $this->recipient_address !== null && strcasecmp($this->recipient_address, $user->email) === 0;
    }

    /**
     * @return Attribute<string, never>
     */
    protected function channelLabel(): Attribute
    {
        return Attribute::get(function (): string {
            $key = "message/messages.channels.{$this->channel}";

            if (trans()->has($key)) {
                return (string) __($key);
            }

            $class = str($this->channel)->classBasename();
            $name = $class->chopEnd('Channel');

            if ($name->isEmpty()) {
                return $class->headline()->value();
            }

            return $name->headline()->value();
        });
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function content(): Attribute
    {
        return Attribute::get(function (): ?string {
            if ($this->channel === 'mail') {
                return $this->subject;
            }

            return $this->text_body;
        });
    }

    /**
     * @return Attribute<MessageFailureReason|null, never>
     */
    protected function failureReason(): Attribute
    {
        return Attribute::get(fn (): ?MessageFailureReason => MessageFailureReason::fromError($this->error, $this->channel));
    }

    /**
     * @return Attribute<Notification|null, never>
     */
    protected function notification(): Attribute
    {
        return Attribute::get(function (): ?Notification {
            try {
                $notification = $this->payload === null ? null : unserialize($this->payload);
            } catch (Throwable) {
                return null;
            }

            if (! $notification instanceof Notification) {
                return null;
            }

            return $notification;
        })->shouldCache();
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function stuck(): Attribute
    {
        return Attribute::get(fn (): bool => $this->status === MessageStatus::Sending && $this->updated_at?->lt(now()->subMinutes(self::STUCK_AFTER_MINUTES)) === true);
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function resendable(): Attribute
    {
        return Attribute::get(function (): bool {
            if ($this->status === MessageStatus::Sending && ! $this->stuck) {
                return false;
            }

            return in_array($this->channel, ['mail', 'sms'], true) && $this->notification instanceof Notification;
        });
    }
}
