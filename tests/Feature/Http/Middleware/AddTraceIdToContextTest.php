<?php

declare(strict_types=1);

use Illuminate\Support\Str;

test('each request gets a new trace id', function (): void {
    $traceId = $this->get('/')->headers->get('X-Request-Id');

    expect(Str::isUuid($traceId))->toBeTrue()
        ->and(context()->get('trace_id'))->toBe($traceId);
});

test('a valid incoming request id is used as the trace id', function (): void {
    $this->get('/', ['X-Request-Id' => 'lb-1234.abcd'])->assertHeader('X-Request-Id', 'lb-1234.abcd');

    expect(context()->get('trace_id'))->toBe('lb-1234.abcd');
});

test('an invalid incoming request id is replaced', function (string $requestId): void {
    $traceId = $this->get('/', ['X-Request-Id' => $requestId])->headers->get('X-Request-Id');

    expect(Str::isUuid($traceId))->toBeTrue();
})->with([
    'unsafe characters' => "abc\nfake log line",
    'too long' => str_repeat('a', 129),
]);
