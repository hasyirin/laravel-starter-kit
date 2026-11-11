<?php

declare(strict_types=1);

namespace App\Domain\Shared\Mail\Models;

use App\Domain\Shared\Common\Models\Model;
use App\Domain\Shared\Mail\Factories\MailLinkFactory;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $mail_id
 * @property string $token
 * @property string $url
 * @property bool $requires_auth
 * @property CarbonImmutable|null $clicked_at
 * @property CarbonImmutable|null $viewed_at
 * @property-read Mail $mail
 */
class MailLink extends Model
{
    /** @use HasFactory<MailLinkFactory> */
    use HasFactory;

    protected $fillable = [
        'mail_id',
        'token',
        'url',
        'requires_auth',
    ];

    protected $hidden = [
        'token',
    ];

    protected $attributes = [
        'requires_auth' => false,
    ];

    protected function casts(): array
    {
        return [
            'requires_auth' => 'boolean',
            'clicked_at' => 'datetime',
            'viewed_at' => 'datetime',
        ];
    }

    protected static function newFactory(): MailLinkFactory
    {
        return MailLinkFactory::new();
    }

    /**
     * @return BelongsTo<Mail, $this>
     */
    public function mail(): BelongsTo
    {
        return $this->belongsTo(Mail::class);
    }

    protected function shouldLogEvent(string $eventName): bool
    {
        return false;
    }

    public function recordClick(): void
    {
        $this->forceFill(['clicked_at' => $this->clicked_at ?? now()])->save();
        $this->mail->forceFill(['clicked_at' => $this->mail->clicked_at ?? now()])->save();
    }

    public function recordView(): void
    {
        $this->forceFill(['viewed_at' => $this->viewed_at ?? now()])->save();
        $this->mail->forceFill(['viewed_at' => $this->mail->viewed_at ?? now()])->save();
    }
}
