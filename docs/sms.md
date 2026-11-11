# SMS

The app sends SMS through Laravel notifications. A notification that implements `SmsNotification` returns `SmsChannel` in `via()` and gives the text in `toSms()`. The channel reads the number from `User::routeNotificationForSms()`. Then it sends the text through the gateway that `SMS_GATEWAY` names.

`LogGateway` is the only gateway in the kit. It writes each SMS to the log, so the codes show in the log during local work. In production it throws an exception, so an app cannot go live without a real gateway. See [ADR-0012](adr/0012-the-kit-sends-sms-through-its-own-channel.md).

## Add a Gateway

1. Write a class that implements `SmsGateway`. Its `send()` gets the number in E.164 format, for example +60123456789, and the text. Mark the number and the text with `#[\SensitiveParameter]`, as `SmsGateway` does, so a stack trace does not show them.
2. Add the class to `system.sms.gateways` in `config/system.php` under a short name.
3. On each server that must send real SMS, set `SMS_GATEWAY` to that name. For local work, keep the default, the log gateway. `phpunit.xml` sets the log gateway for the tests, so a test never sends a real SMS.
4. Keep the credentials of the provider in the environment, and read them in `config/services.php`.

```php
class AcmeGateway implements SmsGateway
{
    public function send(#[\SensitiveParameter] string $phone, #[\SensitiveParameter] string $message): void
    {
        Http::withToken(config()->string('services.acme.token'))
            ->post('https://sms.acme.example/messages', ['to' => $phone, 'text' => $message])
            ->throw();
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

`system.sms.calling_codes` lists the calling codes that the app sends SMS to. The default is +60. The channel throws an exception for a number with another calling code. The phone verification page and the SMS password reset check the list first. The verification page asks the user to change the number or to ask staff, and the reset page sends nothing.

## Codes and Limits

`SmsCode` keeps each one-time code in the cache. A code has 6 digits and expires after 10 minutes. It stops working after 5 wrong tries, and a new code replaces the old one. A code belongs to a number and a purpose, so a verification code cannot reset a password.

`SmsCodeLimit` allows 1 SMS code per minute and 5 per hour for each key. Phone verification counts per user. The SMS password reset counts per number and per IP address, also for a number that belongs to no user. The limit counts only codes. `SmsChannel` has no limit, so other SMS notifications do not count toward it.

`SmsPasswordResetLock` counts the failed codes on the SMS password reset for each number. A code fails when it is wrong, expired, used, or out of tries. The cache no longer holds an expired or used code, so the server cannot tell it from a wrong one. A code that expires unused does not count. After 10 failed codes, it locks the SMS password reset for the number until 24 hours after the first failed code. It counts for every number, also for a number that belongs to no user, so the lock does not show which numbers have an account. During the lock, the reset page sends no code to the number and refuses every code, also the right one. The user can still reset the password by email.

`SmsCode`, `SmsCodeLimit`, and `SmsPasswordResetLock` hash the number before it goes into a cache key. So a list of the cache keys shows no phone numbers.

## Before You Go Live

1. Add a gateway for the SMS provider of the organization, and set `SMS_GATEWAY` on the server. See Add a Gateway.
2. Set a spend cap at the SMS provider. Public pages, such as registration and the SMS password reset, can make the app send SMS. The limits slow an attack, but the provider bills each SMS.
3. If the app runs behind a proxy or a load balancer, trust it with `$middleware->trustProxies()` in `bootstrap/app.php`. Otherwise every request has the IP address of the proxy, and all visitors share the per-IP limit of the SMS password reset.
4. Serve the app with PHP-FPM, FrankenPHP, or LiteSpeed. These servers send the response before the SMS password reset sends its code. On a server that cannot do this, such as `php artisan serve`, the page waits for the SMS. The response time then shows which numbers have an account.
5. Run Horizon. See [Deployment](../README.md#deployment). The SMS password reset sends its alert emails, `SmsPasswordResetStarted` and `PasswordWasReset`, on the queue. If `QUEUE_CONNECTION` is `sync`, they go out during the request, and a mail failure breaks the reset.

## Test SMS

Fake the notifications, and read the code from the notification that the test sent:

```php
Notification::fake();

SendPhoneVerificationCode::make()->execute($user);

$code = Notification::sent($user, PhoneVerificationCodeSms::class)->first()->code;
```

To test a gateway, bind `Tests\Fixtures\Gateways\FakeGateway`. It records each SMS in `$sent`.
