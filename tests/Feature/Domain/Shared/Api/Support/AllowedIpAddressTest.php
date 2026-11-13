<?php

declare(strict_types=1);

use App\Domain\Shared\Api\Support\AllowedIpAddress;

test('an allowed IP address is an IPv4 or IPv6 address, or a CIDR range with a valid prefix length', function (string $value, bool $valid): void {
    expect(AllowedIpAddress::valid($value))->toBe($valid);
})->with([
    'an IPv4 address' => ['192.0.2.10', true],
    'an IPv6 address' => ['2001:db8::1', true],
    'an IPv4 range' => ['198.51.100.0/24', true],
    'an IPv4 range of one address' => ['198.51.100.7/32', true],
    'an IPv6 range' => ['2001:db8::/32', true],
    'an IPv6 range of one address' => ['2001:db8::1/128', true],
    'an IPv4 prefix that is too long' => ['198.51.100.0/33', false],
    'an IPv6 prefix that is too long' => ['2001:db8::/129', false],
    'an IPv4 range of every address' => ['0.0.0.0/0', false],
    'an IPv6 range of every address' => ['::/0', false],
    'a zero prefix with a leading zero' => ['198.51.100.0/00', false],
    'a prefix that is not a number' => ['198.51.100.0/abc', false],
    'a negative prefix' => ['198.51.100.0/-1', false],
    'an empty prefix' => ['198.51.100.0/', false],
    'two prefixes' => ['198.51.100.0/24/8', false],
    'an address out of range' => ['192.0.2.256', false],
    'a host name' => ['localhost', false],
    'a wildcard' => ['*', false],
]);
