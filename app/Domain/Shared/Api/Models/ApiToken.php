<?php

declare(strict_types=1);

namespace App\Domain\Shared\Api\Models;

use App\Domain\Shared\Api\Factories\ApiTokenFactory;
use App\Domain\Shared\Api\Support\ApiTokenFormat;
use App\Domain\Shared\Common\Models\Model;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use SensitiveParameter;

/**
 * @property int $id
 * @property int $api_client_id
 * @property string $token_hash
 * @property string $last_four
 * @property list<string> $scopes
 * @property CarbonImmutable|null $expires_at
 * @property CarbonImmutable|null $revoked_at
 * @property CarbonImmutable|null $last_used_at
 * @property-read ApiClient|null $apiClient
 * @property-read bool $expired
 * @property-read bool $revoked
 */
class ApiToken extends Model
{
    /** @use HasFactory<ApiTokenFactory> */
    use HasFactory;

    public const int LAST_USE_INTERVAL_SECONDS = 60;

    protected $fillable = [
        'token_hash',
        'last_four',
        'scopes',
        'expires_at',
        'revoked_at',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected $attributes = [
        'scopes' => '[]',
    ];

    protected function casts(): array
    {
        return [
            'scopes' => 'array',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    protected static function newFactory(): ApiTokenFactory
    {
        return ApiTokenFactory::new();
    }

    /**
     * @return BelongsTo<ApiClient, $this>
     */
    public function apiClient(): BelongsTo
    {
        return $this->belongsTo(ApiClient::class);
    }

    /**
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereNull('revoked_at')
            ->where(fn (Builder $query): Builder => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public static function findByToken(#[SensitiveParameter] string $token): ?self
    {
        if (! ApiTokenFormat::valid($token)) {
            return null;
        }

        $hash = ApiTokenFormat::hash($token);
        $apiToken = self::query()->where('token_hash', $hash)->first();

        if ($apiToken === null || ! hash_equals($apiToken->token_hash, $hash)) {
            return null;
        }

        return $apiToken;
    }

    public function recordUse(): void
    {
        if ($this->last_used_at?->gt(now()->subSeconds(self::LAST_USE_INTERVAL_SECONDS)) === true) {
            return;
        }

        $this->forceFill(['last_used_at' => now()])->saveQuietly();
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function expired(): Attribute
    {
        return Attribute::get(fn (): bool => $this->expires_at?->lte(now()) === true);
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function revoked(): Attribute
    {
        return Attribute::get(fn (): bool => $this->revoked_at !== null);
    }
}
