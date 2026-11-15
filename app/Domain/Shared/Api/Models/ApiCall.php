<?php

declare(strict_types=1);

namespace App\Domain\Shared\Api\Models;

use App\Domain\Shared\Api\Enums\ApiCallDirection;
use App\Domain\Shared\Api\Enums\ApiCallOutcome;
use App\Domain\Shared\Api\Factories\ApiCallFactory;
use App\Domain\Shared\Common\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property ApiCallDirection $direction
 * @property int|null $api_client_id
 * @property string|null $api_client_name
 * @property int|null $api_token_id
 * @property string|null $service
 * @property string|null $resource_type
 * @property int|null $resource_id
 * @property string $method
 * @property string $url
 * @property string|null $route_name
 * @property int|null $status
 * @property ApiCallOutcome $outcome
 * @property int $duration_ms
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $trace_id
 * @property int|null $request_size
 * @property int|null $response_size
 * @property string|null $request_body
 * @property string|null $response_body
 * @property string|null $error
 * @property-read ApiClient|null $apiClient
 * @property-read ApiToken|null $apiToken
 */
class ApiCall extends Model
{
    /** @use HasFactory<ApiCallFactory> */
    use HasFactory;

    protected $fillable = [
        'direction',
        'api_client_id',
        'api_client_name',
        'api_token_id',
        'service',
        'resource_type',
        'resource_id',
        'method',
        'url',
        'route_name',
        'status',
        'outcome',
        'duration_ms',
        'ip_address',
        'user_agent',
        'trace_id',
        'request_size',
        'response_size',
        'request_body',
        'response_body',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'direction' => ApiCallDirection::class,
            'status' => 'integer',
            'outcome' => ApiCallOutcome::class,
            'duration_ms' => 'integer',
            'request_size' => 'integer',
            'response_size' => 'integer',
        ];
    }

    protected static function newFactory(): ApiCallFactory
    {
        return ApiCallFactory::new();
    }

    /**
     * @return BelongsTo<ApiClient, $this>
     */
    public function apiClient(): BelongsTo
    {
        return $this->belongsTo(ApiClient::class)->withTrashed();
    }

    /**
     * @return BelongsTo<ApiToken, $this>
     */
    public function apiToken(): BelongsTo
    {
        return $this->belongsTo(ApiToken::class)->withTrashed();
    }

    /**
     * @return MorphTo<\Illuminate\Database\Eloquent\Model, $this>
     */
    public function resource(): MorphTo
    {
        return $this->morphTo();
    }

    protected function shouldLogEvent(string $eventName): bool
    {
        // The API log is a log of its own, so its rows stay out of the audit log.
        return false;
    }
}
