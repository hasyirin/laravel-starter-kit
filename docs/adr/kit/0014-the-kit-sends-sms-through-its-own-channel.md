# The Kit Sends SMS Through Its Own Channel

The kit sends SMS through its own notification channel, `SmsChannel`, and the `SmsGateway` interface. It ships only `LogGateway`, which writes each SMS to the log and throws an exception in production. Each app adds a gateway class for the SMS provider that its organization pays for. It lists the class in `system.sms.gateways`, and sets `SMS_GATEWAY` to its name on the servers that send real SMS. So the kit adds no SMS package, and no app must open an account with a provider that the kit chose. See [SMS](../../sms.md).

## Considered Options

- **The laravel/vonage-notification-channel package**: rejected. It adds a dependency, and every app would need a Vonage account.
- **Twilio through a community package**: rejected for the same reasons.

## Consequences

- An app without a gateway fails at its first SMS in production. Users then do not wait for codes that never come.
- `SMS_GATEWAY` picks the gateway for each server, as `MAIL_MAILER` does for mail. Local work and tests keep the log gateway, so they send no real SMS.
- The app sends SMS only to the calling codes in `system.sms.calling_codes`, which holds +60 by default. Public pages, such as registration and the SMS password reset, can make the app send an SMS. Attackers use such pages to send SMS to premium-rate numbers that they own, and the app pays for each SMS. A user with a number from another country needs staff to verify the number, and cannot reset the password by SMS.
- SMS codes go out at once, not on the queue, so the phone verification page shows a failed send. The SMS password reset sends after the response, so the response takes the same time for every number. Its failed sends go to the Laravel log and to the message log.
- Each other SMS goes on the queue, if its notification class extends the base class of the message log. See [kit ADR-0015](0015-one-message-log-records-every-channel.md).
