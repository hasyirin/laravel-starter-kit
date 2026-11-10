# SMS

The app sends SMS through Laravel notifications. A notification that implements `SmsNotification` returns `SmsChannel` in `via()` and gives the text in `toSms()`. The channel reads the number from `User::routeNotificationForSms()`. Then it sends the text through the gateway that `system.sms.gateway` names.

`LogGateway` is the only gateway in the kit. It writes each SMS to the log, so the codes show in the log during local work. In production it throws an exception, so an app cannot go live without a real gateway. See [ADR-0011](adr/0011-the-kit-sends-sms-through-its-own-channel.md).

## Add a Gateway

1. Write a class that implements `SmsGateway`. Its `send()` gets the number in E.164 format, for example +60123456789, and the text.
2. Set `system.sms.gateway` in `config/system.php` to that class.
3. Keep the credentials of the provider in the environment, and read them in `config/services.php`.

```php
class AcmeGateway implements SmsGateway
{
    public function send(string $phone, string $message): void
    {
        Http::withToken(config()->string('services.acme.token'))
            ->post('https://sms.acme.example/messages', ['to' => $phone, 'text' => $message])
            ->throw();
    }
}
```

Throw an exception when the provider refuses an SMS. The page then shows an error, and the user does not wait for a code.

## Calling Codes

`system.sms.calling_codes` lists the calling codes that the app sends SMS to. The default is +60. The channel throws an exception for a number with another calling code. The phone verification page and the SMS password reset check the list first. The verification page asks the user to change the number or to ask staff, and the reset page sends nothing.

## Codes and Limits

`SmsCode` keeps each one-time code in the cache. A code has 6 digits and expires after 10 minutes. It stops working after 5 wrong tries, and a new code replaces the old one. A code belongs to a number and a purpose, so a verification code cannot reset a password.

`SmsCodeLimit` allows 1 SMS code per minute and 5 per hour for each key. Phone verification counts per user. The SMS password reset counts per number and per IP address, also for a number that belongs to no user. The limit counts only codes. `SmsChannel` has no limit, so other SMS notifications do not count toward it.

`SmsPasswordResetLock` counts the wrong codes on the SMS password reset for each number. After 10 wrong codes, it locks the SMS reset of the number until 24 hours after the first wrong code. It counts for every number, also for a number that belongs to no user, so the lock does not show which numbers have an account. During the lock, the reset page sends no code to the number and refuses every code, also the right one. The user can still reset the password by email.

## Test SMS

Fake the notifications with `Notification::fake()`, and read the code from the notification that the test sent:

```php
Notification::fake();

SendPhoneVerificationCode::make()->execute($user);

$code = Notification::sent($user, PhoneVerificationCode::class)->first()->code;
```

To test a gateway, bind `Tests\Fixtures\Gateways\FakeGateway`. It records each SMS in `$sent`.
