<?php

declare(strict_types=1);

use App\Domain\User\Models\User;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

function auditedRoute(Closure $change): void
{
    Route::post('audited', function () use ($change): Response {
        $change();

        return response()->noContent();
    });
}

function latestAuditEntry(): Activity
{
    return Activity::query()->latest('id')->firstOrFail();
}

test('a request that changes a record gives its audit entry the ip address and the user agent', function (): void {
    auditedRoute(fn () => $this->user->update(['name' => 'Renamed']));

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])
        ->withHeader('User-Agent', 'Test Browser')
        ->post('audited')
        ->assertNoContent();

    expect(latestAuditEntry()->getProperty('ip_address'))->toBe('203.0.113.7')
        ->and(latestAuditEntry()->getProperty('user_agent'))->toBe('Test Browser');
});

test('an audit entry after a job on the sync connection in the same request keeps the ip address and the user agent', function (): void {
    auditedRoute(function (): void {
        dispatch(fn (): null => null)->onConnection('sync');
        $this->user->update(['name' => 'Renamed']);
    });

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])
        ->withHeader('User-Agent', 'Test Browser')
        ->post('audited');

    expect(latestAuditEntry()->getProperty('ip_address'))->toBe('203.0.113.7')
        ->and(latestAuditEntry()->getProperty('user_agent'))->toBe('Test Browser');
});

test('an audit entry from a queue worker has no ip address and no user agent, also when a request queued the job', function (): void {
    $user = $this->user;
    auditedRoute(function () use ($user): void {
        dispatch(fn () => $user->update(['name' => 'Renamed']))->onConnection('database');
    });

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])
        ->withHeader('User-Agent', 'Test Browser')
        ->post('audited');
    app()->instance('request', Request::create('/'));
    $this->artisan('queue:work', ['connection' => 'database', '--once' => true, '--queue' => 'default']);

    $entry = latestAuditEntry();

    expect($entry->attribute_changes?->get('attributes'))->toBe(['name' => 'Renamed'])
        ->and($entry->getProperty('trace_id'))->not->toBeNull()
        ->and($entry->properties?->has('ip_address'))->toBeFalse()
        ->and($entry->properties?->has('user_agent'))->toBeFalse();
});

test('a request with a user agent that is not valid utf-8 still writes its audit entry', function (): void {
    auditedRoute(fn () => $this->user->update(['name' => 'Renamed']));

    $this->withHeader('User-Agent', "Bad\xff")->post('audited')->assertNoContent();

    expect(latestAuditEntry()->getProperty('user_agent'))->toBe('Bad?');
});

test('a long user agent is cut to 512 characters', function (): void {
    auditedRoute(fn () => $this->user->update(['name' => 'Renamed']));

    $this->withHeader('User-Agent', str_repeat('a', 600))->post('audited');

    expect(latestAuditEntry()->getProperty('user_agent'))->toBe(str_repeat('a', 512));
});

test('a user agent with a nul byte is stored without it', function (): void {
    auditedRoute(fn () => $this->user->update(['name' => 'Renamed']));

    $this->withHeader('User-Agent', "Bad\0Agent")->post('audited')->assertNoContent();

    expect(latestAuditEntry()->getProperty('user_agent'))->toBe('BadAgent');
});

test('a user agent is cut to 512 characters after its nul bytes are removed', function (): void {
    auditedRoute(fn () => $this->user->update(['name' => 'Renamed']));

    $this->withHeader('User-Agent', "\0".str_repeat('a', 600))->post('audited');

    expect(latestAuditEntry()->getProperty('user_agent'))->toBe(str_repeat('a', 512));
});

test('the ip address behind a trusted proxy is the address of the person', function (): void {
    TrustProxies::at('10.0.0.1');
    auditedRoute(fn () => $this->user->update(['name' => 'Renamed']));

    $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
        ->withHeader('X-Forwarded-For', '203.0.113.7')
        ->post('audited');

    expect(latestAuditEntry()->getProperty('ip_address'))->toBe('203.0.113.7');
});
