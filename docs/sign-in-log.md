# Sign-in Log

The sign-in log records who used which account, when, how, and from where. It is apart from the audit log and the API log. The [glossary](../GLOSSARY.md#audit) defines the terms: sign-in log and sign-in entry. See [kit ADR-0026](adr/kit/0026-one-sign-in-log-records-who-used-which-account.md).

## What Makes an Entry

The quoted words are the result, or the event for an entry with no result.

| What happens | Entry |
| --- | --- |
| A user signs in with a password, or signs in right after registration. | "Signed in", method Password. |
| A user signs in with a password and a right two-factor code. | One "signed in" entry at the end, with the two-factor mark. |
| A password is right, but the two-factor code is still to come. | No entry. |
| The password is wrong. | "Wrong password". |
| No user matches the sign-in. | "No matching user", with no user and no email address. |
| A user types a wrong two-factor code. | "Wrong two-factor code". Each wrong code makes one entry. |
| A user signs in with a passkey. | "Signed in", method Passkey. |
| The check of a passkey fails. | "Passkey failed". The response is still a server error. Issue #68 tracks this. |
| The rate limit of the login, the two-factor code, or the passkeys at sign-in locks a user out. | One "locked out" entry for each lockout window. |
| A "remember me" cookie renews a session. | No entry, because the session continues. The kit does not record a refused cookie yet. Issue #2 adds it. |
| A user signs out. | "Sign-out". |
| An impersonator starts or stops an impersonation. | One entry for each. |
| A user resets a password. | No entry, because the reset does not sign the user in. |

An entry of an impersonation start or stop names the impersonated user as its user and the impersonator as its impersonator. Each other entry during an impersonation also names the impersonator. See [kit ADR-0008](adr/kit/0008-impersonation-keeps-the-impersonated-user-as-the-actor.md).

## What an Entry Holds

`sign_in_entries` holds one row for each entry:

- `event`: sign-in, failed sign-in, sign-out, impersonation started, or impersonation stopped.
- `result`: signed in, wrong password, no matching user, wrong two-factor code, passkey failed, or locked out. A sign-out and an impersonation entry have no result.
- `method`: Password or Passkey. An entry can have no method.
- `two_factor`: on when the entry is for a two-factor step.
- `user_id` and `impersonator_id`. A row keeps its user after the user is deleted.
- `ip_address`, `user_agent`, and `trace_id` of the request. Behind a proxy that `TRUSTED_PROXIES` does not name, the log stores the IP address of the proxy. See [Trusted Proxies](api.md#trusted-proxies).

The sign-in log never stores the email address of a failed sign-in. An email address can identify a person who has no account. It can also hold a password that a user typed into the wrong field. The IP address already shows an attack from one place.

The rows do not go to the audit log.

## Read the Sign-in Log

- **System page**: `system.logs.sign-ins` opens the page at `/system/logs/sign-ins`. It needs `system.access` and `system.logs.sign-ins.view`. Filter the list by user, result, IP address, and date. The user filter also matches the impersonator.
- **User page**: the sign-in log tab of a user in the internal area shows the entries of the user, and the entries of the user as an impersonator. It needs `internal.controls.users.view-sign-in-log`. It also needs the permissions of the user page: `internal.access`, and `internal.controls.users.view` or `internal.controls.users.view-any` unless the record is the user's own.
- **Security settings**: each user sees their own sign-ins and failed sign-ins of the last 30 days. This needs no permission.

See [Permissions](modules/permission.md).

## Retention

`SIGN_IN_LOG_RETENTION_DAYS` sets how many days the log keeps an entry. The default is 365. If you set it to an empty value, the log keeps every entry.

`ApplySignInLogRetention` runs each day on `SIGN_IN_LOG_QUEUE_CONNECTION`. It writes the older entries to a JSONL file in the folder sign-in-log on the `SIGN_IN_LOG_ARCHIVE_DISK` disk. The default disk is `local`. Then it deletes the entries. It handles 5,000 entries in each job, and it starts a new job for the rest.

## Add a Method or a Result

1. Add a case to `SignInMethod` or `SignInResult`. Add its label to `lang/en/user/sign-in-log.php` and `lang/ms/user/sign-in-log.php`. A result also needs a color in `SignInResult::color()`.
2. For a sign-in method, add the name of its route to `SignInMethod::fromRoute()`. A successful sign-in then gets its entry from the login event of Laravel. A sign-in through a route that `SignInMethod::fromRoute()` does not map gets an entry with no method.
3. For a failure in the app, call `RecordSignInEntry` with the event, the result, the user, and the method:

    ```php
    RecordSignInEntry::make()->execute(SignInEvent::FailedSignIn, SignInResult::PasskeyFailed, $user, SignInMethod::Passkey);
    ```

    The Action class takes the impersonator and the trace ID from Context, and the IP address and the user agent from the request. A call outside a web request, such as in a queued job or a console command, stores no IP address and no user agent.
