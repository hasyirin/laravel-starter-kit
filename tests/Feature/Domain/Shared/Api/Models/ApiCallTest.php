<?php

declare(strict_types=1);

use App\Domain\Shared\Api\Models\ApiCall;
use App\Domain\Shared\Common\Models\AuditEntry;

test('the API log is a log of its own, so its rows stay out of the audit log', function (): void {
    $apiCall = ApiCall::factory()->create();

    $apiCall->delete();

    expect(AuditEntry::query()->where('subject_type', 'api_call')->exists())->toBeFalse();
});
