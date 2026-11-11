# One Message Log Records Every Channel

The kit keeps one log, the message log, for every message that leaves the app through a notification class. A message is an email, an SMS, or a message on a channel that an app adds. Each row is one message on one channel. `RecordMessage` opens and closes the row from the notification events of Laravel, so a new channel needs no log code. A channel gives its own facts, such as the address and the text, to the row through `CurrentMessage`. See [Messages](../../messages.md).

## Considered Options

- **A second log for SMS**: rejected. Staff would look in two places for the messages about one record. Each new channel would also need its own table, page, permissions, and retention job.
- **A log of notification classes only, without the mail events**: rejected. An email without a notification class would leave no row. The recipient, the copy recipients, and the bodies of an email also come only from the mail events.
- **Record an SMS at the gateway**: rejected. The gateway gets only a number and a text. It does not know the notification class, the record, or the values to hide.
- **Rows for the notification under the bell**: rejected. The `notifications` table already holds it, and it does not leave the app.

## Consequences

- The row has columns that only an email fills: the copy recipients, the subject, the HTML body, and the opens, clicks, and views. They are empty for every other channel.
- An SMS gets a row only when it goes through a notification class. A direct call to the gateway leaves no row.
- The `HasMessageLog` contract is opt-in for every channel. A class without it gets a row with the recipient and the status, and no text. So a class from a package, or a new class with a code, cannot put a secret in the log by accident. The subject of an email is the one exception: the row keeps it for every class.
- A row names its channel. The kit registers its SMS channel under the name `sms`, as Laravel names the email channel `mail`. A channel that an app names by its class keeps the class name in the row.
- Sent means that the mail server or the SMS gateway accepted the message. The kit has no status for a message that reached the person.
- One permission pair and one retention period cover every channel.
- The log fails a queue job at once on a `LogicException`, on every channel. Such an exception stays true on each try.
