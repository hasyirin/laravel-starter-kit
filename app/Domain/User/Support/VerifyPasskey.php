<?php

declare(strict_types=1);

namespace App\Domain\User\Support;

use App\Domain\User\Actions\SignInLog\RecordSignInEntry;
use App\Domain\User\Enums\SignInEvent;
use App\Domain\User\Enums\SignInMethod;
use App\Domain\User\Enums\SignInResult;
use App\Domain\User\Models\User;
use Laravel\Passkeys\Actions\VerifyPasskey as BaseVerifyPasskey;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\Exceptions\InvalidPasskeyException;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Passkeys;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\Exception\AuthenticatorResponseVerificationException;
use Webauthn\Exception\CounterException;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialRequestOptions;

class VerifyPasskey extends BaseVerifyPasskey
{
    public function __invoke(
        PublicKeyCredential $credential,
        PublicKeyCredentialRequestOptions $options,
        ?PasskeyUser $user = null,
    ): Passkey {
        try {
            return parent::__invoke($credential, $options, $user);
            // @phpstan-ignore catch.neverThrown (the assertion check in parent::__invoke() throws it without a @throws tag), catch.neverThrown (the counter check in parent::__invoke() throws it without a @throws tag)
        } catch (InvalidPasskeyException|AuthenticatorResponseVerificationException|CounterException $exception) {
            if (! $user instanceof PasskeyUser && $credential->response instanceof AuthenticatorAssertionResponse) {
                RecordSignInEntry::make()->execute(
                    SignInEvent::FailedSignIn,
                    SignInResult::PasskeyFailed,
                    self::user(Base64UrlSafe::encodeUnpadded($credential->rawId)),
                    SignInMethod::Passkey,
                );
            }

            throw $exception;
        }
    }

    public static function user(string $credentialId): ?User
    {
        $user = Passkeys::passkeyModel()::query()
            ->where('credential_id', $credentialId)
            ->first()
            ?->user;

        return $user instanceof User ? $user : null;
    }
}
