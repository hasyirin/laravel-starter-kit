<?php

declare(strict_types=1);

use App\Domain\Shared\Api\Actions\DeleteApiClient;
use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\Shared\Api\Models\ApiToken;
use App\Domain\Shared\Api\Support\ApiClientAuditLog;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    $this->apiClient = ApiClient::factory()->create();
    $this->apiToken = ApiToken::factory()->for($this->apiClient)->create();
    ApiToken::factory()->for(ApiClient::factory())->create();
});

test('the API client log holds changes to the API client and its API tokens only', function (): void {
    $subjects = ApiClientAuditLog::query($this->apiClient)->get()
        ->map(fn (Activity $activity): string => $activity->subject_type.':'.$activity->subject_id)
        ->sort()
        ->values()
        ->all();

    expect($subjects)->toBe(['api_client:'.$this->apiClient->id, 'api_token:'.$this->apiToken->id]);
});

test('the log lists the newest entry first', function (): void {
    $this->apiClient->update(['name' => 'Renamed']);

    expect(ApiClientAuditLog::query($this->apiClient)->first()->event)->toBe('updated');
});

test('entries stay in the log after the API client is deleted', function (): void {
    DeleteApiClient::make()->execute($this->apiClient);

    expect(ApiClientAuditLog::query($this->apiClient)->where('event', 'deleted')->exists())->toBeTrue()
        ->and(ApiClientAuditLog::query($this->apiClient)->whereMorphedTo('subject', $this->apiToken)->where('event', 'updated')->exists())->toBeTrue();
});

test('an entry of another API client is not found', function (): void {
    $entry = Activity::query()->where('subject_type', 'api_token')->whereKeyNot(ApiClientAuditLog::query($this->apiClient)->pluck('id'))->firstOrFail();

    ApiClientAuditLog::find($this->apiClient, $entry->id);
})->throws(ModelNotFoundException::class);
