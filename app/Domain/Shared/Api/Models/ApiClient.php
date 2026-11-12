<?php

declare(strict_types=1);

namespace App\Domain\Shared\Api\Models;

use App\Domain\Shared\Api\Factories\ApiClientFactory;
use App\Domain\Shared\Common\Models\Model;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property bool $enabled
 * @property list<string> $allowed_ip_addresses
 * @property bool $allows_any_ip_address
 * @property int $rate_limit_per_minute
 * @property string|null $service
 * @property-read Collection<int, ApiToken> $tokens
 * @property-read Collection<int, ApiToken> $activeTokens
 */
class ApiClient extends Model implements AuthenticatableContract
{
    use Authenticatable;

    /** @use HasFactory<ApiClientFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'enabled',
        'allowed_ip_addresses',
        'allows_any_ip_address',
        'rate_limit_per_minute',
        'service',
    ];

    protected $attributes = [
        'enabled' => true,
        'allowed_ip_addresses' => '[]',
        'allows_any_ip_address' => false,
        'rate_limit_per_minute' => 60,
    ];

    private ?ApiToken $currentToken = null;

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'allowed_ip_addresses' => 'array',
            'allows_any_ip_address' => 'boolean',
            'rate_limit_per_minute' => 'integer',
        ];
    }

    protected static function newFactory(): ApiClientFactory
    {
        return ApiClientFactory::new();
    }

    /**
     * @return HasMany<ApiToken, $this>
     */
    public function tokens(): HasMany
    {
        return $this->hasMany(ApiToken::class);
    }

    /**
     * @return HasMany<ApiToken, $this>
     */
    public function activeTokens(): HasMany
    {
        return $this->tokens()->active();
    }

    public function allowsIpAddress(string $ipAddress): bool
    {
        if ($this->allows_any_ip_address) {
            return true;
        }

        if ($this->allowed_ip_addresses === []) {
            return false;
        }

        return IpUtils::checkIp($ipAddress, $this->allowed_ip_addresses);
    }

    public function withToken(ApiToken $apiToken): static
    {
        $this->currentToken = $apiToken;

        return $this;
    }

    public function currentToken(): ?ApiToken
    {
        return $this->currentToken;
    }

    public function tokenCan(string $scope): bool
    {
        return in_array($scope, $this->currentToken->scopes ?? [], true);
    }

    public function getAuthPassword(): string
    {
        return '';
    }

    public function getRememberTokenName(): string
    {
        return '';
    }
}
