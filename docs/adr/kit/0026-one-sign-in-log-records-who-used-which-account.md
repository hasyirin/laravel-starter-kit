# One Sign-in Log Records Who Used Which Account

The kit keeps one log, the sign-in log, for each sign-in, failed sign-in, sign-out, ended session, expired session, and impersonation. The sign-in log is apart from the audit log, the message log, and the API log. It answers who used which account, when, how, and from where.

## Considered Options

- **Sign-ins in the audit log, with a new event**: rejected. The audit log records changes to records. A sign-in changes no record. [Kit ADR-0020](0020-one-api-log-records-both-directions.md) keeps API calls out of the audit log for the same reason.
- **Keep the email address of a failed sign-in when no user matches**: rejected. It can identify a person who has no account, and it can hold a password typed into the wrong field. The IP address already shows an attack from one place.
- **No entries for impersonation**: rejected. An impersonator who only views pages changes no record, so the audit log does not show the impersonation.

## Consequences

- Impersonation starts and stops get entries. Each entry names the impersonated user as its user and the impersonator as its impersonator, the same as each other entry during an impersonation. This does not go against [kit ADR-0008](0008-impersonation-keeps-the-impersonated-user-as-the-actor.md). That ADR keeps start and stop entries out of the audit log, because they cannot show which changes an impersonator made. The audit log still finds those changes through the impersonator's id on each audit entry.
- A failed sign-in with no matching user stores no email address. It stores the IP address, the user agent, and the result.
- A "remember me" renewal is not a sign-in, because the session continues. A remember cookie that the kit refuses is a failed sign-in. [Kit ADR-0027](0027-sessions-are-long-lived-and-the-kit-has-no-remember-me.md) removes remember me, so the kit has no remember cookie to refuse.
- The sign-in log has its own permission and its own retention period.
