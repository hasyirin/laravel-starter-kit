# Every User Verifies a Phone Number

Every user has a phone number and verifies it with an SMS code before they use the app, in the same way as the email address. The `phone.verified` middleware stands next to every `verified` middleware. A user who holds `bypass-authorization` passes both checks and needs no phone number. `User` implements `MustVerifyPhone` next to `Illuminate\Contracts\Auth\MustVerifyEmail`. See [kit ADR-0014](0014-the-kit-sends-sms-through-its-own-channel.md) for how the app sends SMS.

## Considered Options

- **An optional number, with the guard only on the routes that need it**: rejected. The kit treats the number like the email: every user has one, and every area checks it. An app that does not need a verified number removes `MustVerifyPhone` from `User`. Then `phone.verified` lets every user pass.
- **Exempt every user who can open the system area**: rejected. Staff who can change permissions can grant `system.access`, so they could exempt any user. Only a holder of `bypass-authorization` can grant `bypass-authorization`.

## Consequences

- Every app needs an SMS gateway before it goes live. See [SMS](../../sms.md).
- A user who cannot get SMS, for example a user with a number outside the calling codes, needs staff to mark the number as verified.
- The `verified` alias points to `App\Http\Middleware\EnsureEmailIsVerified`, which lets a holder of `bypass-authorization` pass. Such a user can use the app with an unverified email.
- A holder of `bypass-authorization` without a number who loses that permission lands on the phone verification page. The page asks them to add a number in their settings.
- The phone verification page sends a code when it opens, unless an unused code exists. During impersonation, it sends no code when it opens, but its resend button still sends one.
