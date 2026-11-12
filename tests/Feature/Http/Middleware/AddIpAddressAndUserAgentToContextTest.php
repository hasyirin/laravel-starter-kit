<?php

declare(strict_types=1);

use Illuminate\Http\Middleware\TrustProxies;

test('each request adds the ip address and the user agent to the context', function (): void {
    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])
        ->withHeader('User-Agent', 'Test Browser')
        ->get('/');

    expect(context()->getHidden('ip_address'))->toBe('203.0.113.7')
        ->and(context()->getHidden('user_agent'))->toBe('Test Browser');
});

test('the ip address behind a trusted proxy is the address of the person', function (): void {
    TrustProxies::at('10.0.0.1');

    $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
        ->withHeader('X-Forwarded-For', '203.0.113.7')
        ->get('/');

    expect(context()->getHidden('ip_address'))->toBe('203.0.113.7');
});
