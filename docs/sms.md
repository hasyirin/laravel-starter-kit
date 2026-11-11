# SMS

The app sends SMS through Laravel notification classes. A notification class that implements `SmsNotification` returns the channel name `sms` in `via()` and gives the text in `toSms()`. Do not return the class of the channel there: the row in the message log then keeps the class name as its channel, not `sms`. `SmsChannel` reads the number from `User::routeNotificationForSms()`. Then it sends the text through the gateway that `SMS_GATEWAY` names.

The message log gets a row for each SMS that goes through the channel. A direct call to the gateway leaves no row and skips the intercept. So always send an SMS through a notification class.

`LogGateway` is the only gateway in the kit. It writes each SMS to the log, so the codes show in the log during local work. In production it throws an exception, so an app cannot go live without a real gateway. See [kit ADR-0014](adr/kit/0014-the-kit-sends-sms-through-its-own-channel.md).

## Add a Gateway

1. Write a class that implements `SmsGateway`. Its `send()` gets the number in E.164 format, for example +60123456789, and the text. It returns the ID that the provider gives to the SMS as a string, or `null` if the provider gives none. The message log keeps this ID. Mark the number and the text with `#[\SensitiveParameter]`, as `SmsGateway` does, so a stack trace does not show them.
2. Add the class to `system.sms.gateways` in `config/system.php` under a short name.
3. On each server that must send real SMS, set `SMS_GATEWAY` to that name. For local work, keep the default, the log gateway. `phpunit.xml` sets the log gateway and turns the intercept off for the tests. So a test never sends a real SMS, and it does not depend on your `.env`.
4. Keep the credentials of the provider in the environment, and read them in `config/services.php`.

```php
class AcmeGateway implements SmsGateway
{
    public function send(#[\SensitiveParameter] string $phone, #[\SensitiveParameter] string $message): ?string
    {
        $id = Http::withToken(config()->string('services.acme.token'))
            ->post('https://sms.acme.example/messages', ['to' => $phone, 'text' => $message])
            ->throw()
            ->json('id');

        if (! is_scalar($id)) {
            return null;
        }

        return (string) $id;
    }
}
```

```php
// config/system.php
'gateways' => [
    'log' => LogGateway::class,
    'acme' => AcmeGateway::class,
],
```

Throw an exception when the provider refuses an SMS. The phone verification page then reports the exception and asks the user to try again later. The code that did not go out stops working, so a later visit sends a new one. The failed send still counts toward the limits. The SMS password reset sends its code after the response, so the response takes the same time for every number. There, a failed send goes to the log.

## Calling Codes

`system.sms.calling_codes` lists the calling codes that the app sends SMS to. The default is +60. The channel throws an exception for a number with another calling code. The phone verification page and the SMS password reset check the list first. The resend form of the message log checks it too, with the `AllowedCallingCode` rule. The verification page asks the user to change the number or to ask staff, and the reset page sends nothing.

## Intercept

Outside production, the intercept sends each SMS to the signed-in user, not to its real number. Use it on a local or staging copy with real data and a real gateway, so no SMS reaches a real person.

- `SMS_INTERCEPT` turns the intercept on or off. If you do not set it, it follows `MAIL_INTERCEPT`. So a server that intercepts email also intercepts SMS.
- The SMS goes to the phone number of the signed-in user. For a queued SMS, that is the user who was signed in when the app queued it. During impersonation, it is the impersonator.
- If no user is signed in, or that user has no phone number, the SMS goes to `SMS_INTERCEPT_PHONE`.
- If that is empty too, the app drops the SMS and writes a line to the log. The line names the row in the message log, not the phone number.
- The intercept is always off in production.

The message log keeps the real number of the SMS, and shows the number that got it as "Intercepted to". A dropped SMS gets a failed row.

The channel checks the calling code of the real number first, so a staging server fails as production does. Then it also checks the number that gets the SMS.

## Codes and Limits

`SmsCode` keeps each one-time code in the cache. A code has 6 digits and expires after 10 minutes. It stops working after 5 wrong tries, and a new code replaces the old one. A code belongs to a number and a purpose, so a verification code cannot reset a password.

`SmsCodeLimit` allows 1 SMS code per minute and 5 per hour for each key. Phone verification counts per user. The SMS password reset counts per number and per IP address, also for a number that belongs to no user. The limit counts only codes. `SmsChannel` has no limit, so other SMS notification classes do not count toward it.

`SmsPasswordResetLock` counts the failed codes on the SMS password reset for each number. A code fails when it is wrong, expired, used, or out of tries. The cache no longer holds an expired or used code, so the server cannot tell it from a wrong one. A code that expires unused does not count. After 10 failed codes, it locks the SMS password reset for the number until 24 hours after the first failed code. It counts for every number, also for a number that belongs to no user, so the lock does not show which numbers have an account. During the lock, the reset page sends no code to the number and refuses every code, also the right one. The user can still reset the password by email.

`SmsCode`, `SmsCodeLimit`, and `SmsPasswordResetLock` hash the number before it goes into a cache key. So a list of the cache keys shows no phone numbers.

## Before You Go Live

1. Add a gateway for the SMS provider of the organization, and set `SMS_GATEWAY` on the server. See Add a Gateway.
2. Set a spend cap at the SMS provider. Public pages, such as registration and the SMS password reset, can make the app send SMS. The limits slow an attack, but the provider bills each SMS.
3. If the app runs behind a proxy or a load balancer, trust it with `$middleware->trustProxies()` in `bootstrap/app.php`. Otherwise every request has the IP address of the proxy, and all visitors share the per-IP limit of the SMS password reset.
4. Serve the app with PHP-FPM, FrankenPHP, or LiteSpeed. These servers send the response before the SMS password reset sends its code. On a server that cannot do this, such as `php artisan serve`, the page waits for the SMS. The response time then shows which numbers have an account.
5. Run Horizon. See [Deployment](../README.md#deployment). The SMS password reset sends 2 emails, `SmsPasswordResetStarted` and `PasswordWasReset`, on the queue. If `QUEUE_CONNECTION` is `sync`, they go out during the request, and a failed email breaks the reset.

## Test SMS

Fake Laravel notifications, and read the code from the `PhoneVerificationCodeSms` that the test sent:

```php
Notification::fake();

SendPhoneVerificationCode::make()->execute($user);

$code = Notification::sent($user, PhoneVerificationCodeSms::class)->first()->code;
```

To test a gateway, bind `Tests\Fixtures\Gateways\FakeGateway`. It records each SMS in `$sent`.
