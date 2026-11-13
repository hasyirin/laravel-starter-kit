<?php

declare(strict_types=1);

namespace App\Domain\Shared\Api\Actions;

use App\Domain\Shared\Api\Enums\ApiCallDirection;
use App\Domain\Shared\Api\Enums\ApiCallOutcome;
use App\Domain\Shared\Api\Models\ApiCall;
use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\Shared\Api\Models\ApiToken;
use App\Domain\Shared\Api\Support\MaskedBody;
use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use Illuminate\Database\Eloquent\Model;
use SensitiveParameter;

class RecordApiCall
{
    use Actionable;

    public function execute(
        ApiCallDirection $direction,
        string $method,
        string $url,
        ApiCallOutcome $outcome,
        int $durationMs,
        ?int $status = null,
        ?ApiClient $apiClient = null,
        ?ApiToken $apiToken = null,
        ?string $service = null,
        ?Model $resource = null,
        ?string $routeName = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?string $traceId = null,
        ?int $requestSize = null,
        ?int $responseSize = null,
        #[SensitiveParameter] ?string $requestBody = null,
        #[SensitiveParameter] ?string $responseBody = null,
        ?string $error = null,
    ): ApiCall {
        return ApiCall::query()->create([
            'direction' => $direction,
            'api_client_id' => $apiClient?->id,
            'api_client_name' => $apiClient?->name,
            'api_token_id' => $apiToken?->id,
            'service' => $service,
            'resource_type' => $resource?->getMorphClass(),
            'resource_id' => $resource?->getKey(),
            'method' => $method,
            'url' => $this->text($url),
            'route_name' => $routeName,
            'status' => $status,
            'outcome' => $outcome,
            'duration_ms' => $durationMs,
            'ip_address' => $ipAddress,
            'user_agent' => $this->text($userAgent),
            'trace_id' => $traceId ?? context()->get('trace_id'),
            'request_size' => $requestSize,
            'response_size' => $responseSize,
            'request_body' => $this->text(MaskedBody::of($requestBody)),
            'response_body' => $this->text(MaskedBody::of($responseBody)),
            'error' => $this->text($error),
        ]);
    }

    /**
     * PostgreSQL refuses bytes that are not UTF-8 and NUL bytes in a text column.
     */
    private function text(#[SensitiveParameter] ?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return str(mb_scrub($value, 'UTF-8'))->remove("\0")->value();
    }
}
