<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Enums\Feature;
use App\Domain\User\Enums\SignInEvent;
use App\Domain\User\Enums\SignInMethod;
use App\Domain\User\Enums\SignInResult;
use App\Domain\User\Models\SignInEntry;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserSession;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Passkeys;
use Laravel\Passkeys\Support\WebAuthn;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Uid\Uuid;
use Webauthn\CredentialRecord;
use Webauthn\TrustPath\EmptyTrustPath;

beforeEach(function (): void {
    $this->skipUnlessFeature(Feature::Passkeys);
});

function passkeySigningKey(): OpenSSLAsymmetricKey
{
    $key = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
    throw_unless($key instanceof OpenSSLAsymmetricKey, RuntimeException::class, 'The test key could not be made.');

    return $key;
}

function storeSigningPasskey(User $user, OpenSSLAsymmetricKey $key, string $rawId, int $counter = 0): Passkey
{
    $details = openssl_pkey_get_details($key);
    $x = str_pad($details['ec']['x'], 32, "\0", STR_PAD_LEFT);
    $y = str_pad($details['ec']['y'], 32, "\0", STR_PAD_LEFT);
    $coseKey = "\xA5\x01\x02\x03\x26\x20\x01\x21\x58\x20".$x."\x22\x58\x20".$y;

    $record = CredentialRecord::create(
        publicKeyCredentialId: $rawId,
        type: 'public-key',
        transports: [],
        attestationType: 'none',
        trustPath: EmptyTrustPath::create(),
        aaguid: Uuid::fromString('00000000-0000-0000-0000-000000000000'),
        credentialPublicKey: $coseKey,
        userHandle: 'user-handle',
        counter: $counter,
    );

    return $user->passkeys()->create([
        'name' => 'Work laptop',
        'credential_id' => Base64UrlSafe::encodeUnpadded($rawId),
        'credential' => json_decode(WebAuthn::toJson($record), true, flags: JSON_THROW_ON_ERROR),
    ]);
}

/**
 * @return array<string, mixed>
 */
function passkeyAssertion(string $rawId, OpenSSLAsymmetricKey $key, string $challenge, int $signCount = 1, bool $signedRight = true): array
{
    $clientData = json_encode([
        'type' => 'webauthn.get',
        'challenge' => $challenge,
        'origin' => Passkeys::allowedOrigins()[0],
        'crossOrigin' => false,
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    $authenticatorData = hash('sha256', Passkeys::relyingPartyId(), true)."\x05".pack('N', $signCount);
    $signed = $signedRight ? $authenticatorData.hash('sha256', $clientData, true) : 'other data';
    openssl_sign($signed, $signature, $key, OPENSSL_ALGO_SHA256);

    return ['credential' => [
        'id' => Base64UrlSafe::encodeUnpadded($rawId),
        'rawId' => Base64UrlSafe::encodeUnpadded($rawId),
        'type' => 'public-key',
        'response' => [
            'clientDataJSON' => Base64UrlSafe::encodeUnpadded($clientData),
            'authenticatorData' => Base64UrlSafe::encodeUnpadded($authenticatorData),
            'signature' => Base64UrlSafe::encodeUnpadded($signature),
            'userHandle' => Base64UrlSafe::encodeUnpadded('user-handle'),
        ],
    ]];
}

function passkeyChallenge(string $route): string
{
    return test()->getJson(route($route))->assertOk()->json('options.challenge');
}

test('a right passkey signs in and records one passkey sign-in entry', function (): void {
    $user = User::factory()->create();
    $key = passkeySigningKey();
    storeSigningPasskey($user, $key, 'credential-1');
    $challenge = passkeyChallenge('passkey.login-options');

    $this->postJson(route('passkey.login'), passkeyAssertion('credential-1', $key, $challenge))->assertOk();

    $this->assertAuthenticatedAs($user);
    expect(SignInEntry::query()->sole()->only('event', 'result', 'method', 'two_factor', 'user_id'))->toBe([
        'event' => SignInEvent::SignIn,
        'result' => SignInResult::SignedIn,
        'method' => SignInMethod::Passkey,
        'two_factor' => false,
        'user_id' => $user->id,
    ]);
});

test('a passkey sign-in makes one session row for the user', function (): void {
    $user = User::factory()->create();
    $key = passkeySigningKey();
    storeSigningPasskey($user, $key, 'credential-1');
    $challenge = passkeyChallenge('passkey.login-options');

    $this->postJson(route('passkey.login'), passkeyAssertion('credential-1', $key, $challenge))->assertOk();

    expect(UserSession::query()->sole()->only('user_id', 'session_id'))->toBe(['user_id' => $user->id, 'session_id' => session()->getId()]);
});

test('a passkey sign-in that asks to be remembered sets no remember cookie and deletes an old one', function (): void {
    $user = User::factory()->create();
    $key = passkeySigningKey();
    storeSigningPasskey($user, $key, 'credential-1');
    $challenge = passkeyChallenge('passkey.login-options');

    $response = $this->postJson(route('passkey.login'), [...passkeyAssertion('credential-1', $key, $challenge), 'remember' => true])->assertOk();

    $this->assertAuthenticatedAs($user);
    $rememberCookies = collect($response->headers->getCookies())
        ->filter(fn (Cookie $cookie): bool => str($cookie->getName())->startsWith('remember_'))
        ->values();

    expect($rememberCookies)->toHaveCount(1)
        ->sequence(fn ($cookie) => $cookie->isCleared()->toBeTrue());
});

test('a passkey assertion that fails the check records one failed passkey entry of the passkey user after the rollback', function (int $storedCounter, bool $signedRight): void {
    $user = User::factory()->create();
    $key = passkeySigningKey();
    $passkey = storeSigningPasskey($user, $key, 'credential-1', $storedCounter);
    $challenge = passkeyChallenge('passkey.login-options');

    $this->postJson(route('passkey.login'), passkeyAssertion('credential-1', $key, $challenge, signedRight: $signedRight))
        ->assertServerError();

    $this->assertGuest();
    expect($passkey->fresh()?->last_used_at)->toBeNull()
        ->and(SignInEntry::query()->sole()->only('event', 'result', 'method', 'user_id'))->toBe([
            'event' => SignInEvent::FailedSignIn,
            'result' => SignInResult::PasskeyFailed,
            'method' => SignInMethod::Passkey,
            'user_id' => $user->id,
        ]);
})->with([
    'a wrong signature' => [0, false],
    'a replayed counter' => [5, true],
]);

test('an unknown passkey records one failed passkey entry without a user', function (): void {
    $key = passkeySigningKey();
    $challenge = passkeyChallenge('passkey.login-options');

    $this->postJson(route('passkey.login'), passkeyAssertion('unknown-credential', $key, $challenge))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('credential');

    $this->assertGuest();
    expect(SignInEntry::query()->sole()->only('event', 'result', 'method', 'user_id'))->toBe([
        'event' => SignInEvent::FailedSignIn,
        'result' => SignInResult::PasskeyFailed,
        'method' => SignInMethod::Passkey,
        'user_id' => null,
    ]);
});

test('a passkey request with expired options or a bad format records nothing', function (): void {
    $key = passkeySigningKey();
    storeSigningPasskey(User::factory()->create(), $key, 'credential-1');

    $this->postJson(route('passkey.login'), passkeyAssertion('credential-1', $key, 'no-options-in-session'))
        ->assertJsonValidationErrors('credential');
    passkeyChallenge('passkey.login-options');
    $this->postJson(route('passkey.login'), ['credential' => ['id' => 'x', 'rawId' => 'y', 'type' => 'public-key', 'response' => ['a' => 'b']]])
        ->assertJsonValidationErrors('credential');

    expect(SignInEntry::query()->count())->toBe(0);
});

test('a failed passkey confirmation of a signed-in user records nothing', function (): void {
    $user = User::factory()->create();
    $key = passkeySigningKey();
    storeSigningPasskey($user, $key, 'credential-1');
    $this->actingAs($user);
    $challenge = passkeyChallenge('passkey.confirm-options');

    $this->postJson(route('passkey.confirm'), passkeyAssertion('unknown-credential', $key, $challenge))
        ->assertJsonValidationErrors('credential');

    expect(SignInEntry::query()->count())->toBe(0);
});
