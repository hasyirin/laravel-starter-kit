# Messages

The message log has one row for each message that leaves the app: an email, an SMS, or a message on a channel that the app adds. A row holds the recipient, the status, and the record that the message is about. The notification under the bell is not in the log. See [kit ADR-0015](adr/kit/0015-one-message-log-records-every-channel.md).

System administrators read the log at `/system/logs/messages`. The page needs `system.logs.messages.view`, and a resend needs `system.logs.messages.resend`.

## How a Row Is Made

`RecordMessage` listens to the notification events of Laravel. It opens a row when a notification class reaches a channel, and it closes the row when the channel is done.

- One notification class can use two channels. Each channel gets its own row.
- An email without a notification class still gets a row. `RecordMail` makes it from the mail events.
- An SMS gets a row only when it goes through a notification class. See [SMS](sms.md).
- The `database` and `broadcast` channels get no row. They carry the notification under the bell.

## Give a Notification Class a Full Row

Extend `App\Domain\Shared\Message\Notifications\Notification`. The class then has the `HasMessageLog` contract, and it goes on the queue. If the class must extend another class, implement `HasMessageLog` and use `InteractsWithMessageLog`.

```php
class PermitApproved extends Notification implements SmsNotification
{
    public function __construct(public Permit $permit) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'sms'];
    }

    public function about(): Permit
    {
        return $this->permit;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return new MailMessage()->subject('Permit approved')->line('Your permit is approved.');
    }

    public function toSms(object $notifiable): string
    {
        return 'Your permit is approved.';
    }
}
```

The contract has three methods that a class can change:

| Method | What it gives |
| --- | --- |
| `about()` | The record that the message is about. The row links to it, so a record page can list its messages. |
| `sensitive()` | The values that the log must hide, such as a code or a signed link. The row shows "[hidden]" in their place. |
| `tracked()` | For an email only: whether the log counts opens, clicks, and views. The default is `MAIL_TRACKING`. |

A row with sensitive values keeps no copy of the notification class, so it has no resend.

Markdown changes a value that has characters such as `*` or `_`. The log then cannot find the value in the HTML body of an email. Put such a value in an `Illuminate\Support\HtmlString` or in a code span.

Without the contract, a row keeps the recipient and the status. An email also keeps its subject. The row has no text, no record, and no resend. So a class from a package cannot put a secret in the log by accident.

## What a Row Holds

| | Email | SMS | Channel of the app |
| --- | --- | --- | --- |
| Recipient | The address and the name | The phone number | Only if the channel gives it |
| Text | The subject and the bodies | The text of the SMS | Only if the channel gives it |
| Copy recipients and attachments | Yes | No | No |
| Opens, clicks, and views | Yes, see [kit ADR-0010](adr/kit/0010-only-a-signed-in-recipient-makes-a-view.md) | No | No |
| ID from the provider | From the mail server | From the SMS gateway | Only if the channel gives it |
| Resend | Yes | Yes | No |

The bodies of an email and the text of an SMS are in the row only for a class with the contract.

## Statuses

- **Sending**: the app started the send. In a queue job, the row stays in this status between the tries and shows the last error.
- **Sent**: the mail server or the SMS gateway accepted the message. It is not proof that the message reached the person.
- **Failed**: the send did not work. The row shows a reason, a suggestion, and the technical message, with the sensitive values hidden.

A row that stays in "sending" for more than 60 minutes lost its queue job. Staff can resend such a row.

The list shows two times. Started is the time when the app started the send. Sent is the time when the mail server or the SMS gateway accepted the message.

## Queue and Tries

- A class that extends the base class goes on the queue. Laravel makes one queue job for each channel.
- Each job has 4 tries. It waits 1 minute, then 5 minutes, then 15 minutes.
- The job of an email uses `MAIL_QUEUE_CONNECTION`. The job of each other channel uses the default queue connection.
- After the last try, the row of that channel is failed. The rows of the other channels do not change.
- A `LogicException` fails the job at once, on every channel. Throw one when another try cannot succeed.
- An SMS code does not go on the queue. See [kit ADR-0014](adr/kit/0014-the-kit-sends-sms-through-its-own-channel.md).

## Resend

Staff with `system.logs.messages.resend` can resend an email or an SMS from its row. The message goes to the same recipient, or to another email address or phone number. The resend is a new row that links to the first one.

- A row has a resend only if its class has the contract and names no sensitive values. The app builds the notification class again from the row.
- A message on a channel that the app adds has no resend.
- Another phone number must pass the `PhoneNumber` rule and the `AllowedCallingCode` rule.
- A resend to another address or number does not link the new row to the user.
- The list marks the new row as a resend, and the earlier row as resent. The modal of the earlier row lists its resends with their statuses.

A model that gets messages uses `RoutesResentMessages`, as `User` does. A resend then gives that model to the notification class. Without the trait, the notification class gets an `Illuminate\Notifications\AnonymousNotifiable`.

## Intercept

Outside production, an intercept sends each email and SMS to the signed-in user, not to the recipient. The row keeps the real recipient, and it shows the address or number that got the message as "Intercepted to". A message that the intercept drops gets a failed row. See the Intercept bullet in the [README](../README.md) and [SMS](sms.md#intercept).

## Retention

`MESSAGE_RETENTION_DAYS` sets how many days the log keeps a row. Without a value, the log keeps every row.

`ApplyMessageLogRetention` runs each day on `MESSAGE_LOG_QUEUE_CONNECTION`. It writes the older rows to a JSONL file in the folder message-log on the `MESSAGE_ARCHIVE_DISK` disk. Then it deletes the rows and their copies of the attachments.

## Add a Channel

A channel that the app adds gets a row with no new log code. The row has the status, the user, and the record. To give more facts to the row, the channel calls `CurrentMessage`:

```php
class PagerChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        $current = resolve(CurrentMessage::class);
        $text = $notification->toPager($notifiable);

        $current->record($notifiable->pager, $text);

        $current->sent(resolve(Pager::class)->send($notifiable->pager, $text));
    }
}
```

- `record()` gives the address and the text. Call it before the send, so a failed row still shows them. The log keeps the text only for a class with the contract, and it hides the sensitive values.
- `sent()` marks the row as sent and gives the ID from the provider. Without this call, the row is sent when the channel returns.
- `fail()` marks the row as failed when the channel sends nothing and throws nothing. It takes a case of `MessageFailureReason` and a text.
- `intercept()` gives the address that got the message in place of the recipient.
- An exception from the channel also gives a failed row.

Give the channel a short name in a service provider, as `AppServiceProvider` does for `sms`. The row then keeps that name. Without a name, the row keeps the class name of the channel.

## Show the Messages of a Record

A record page can list the messages about its record. The list shows each row whose notification class returns that record from `about()`. The user needs `system.logs.messages.view`.

```blade
<livewire:data-tables.internal.message-log-data-table :resource="$permit" />

<livewire:pages.shared.message-modal />
```

## Test Messages

Send the real notification class, and read the row:

```php
$user->notify(new PermitApproved($permit));

expect(Message::query()->sole()->status)->toBe(MessageStatus::Sent);
```

A fake of the notifications stops the notification events, so a test with it makes no row. For an SMS, bind `Tests\Fixtures\Gateways\FakeGateway`. See [SMS](sms.md#test-sms).
