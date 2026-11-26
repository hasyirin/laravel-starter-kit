# Sessions Are Long-Lived and the Kit Has No Remember Me

The sign-in page has no "remember me" checkbox, and the kit sets no remember cookie. Each sign-in starts one long-lived session. The session ends after 30 days with no request, or 90 days after the sign-in, whichever comes first. It also ends when the user signs out, or when the user or staff end it. This replaces the remember token of each session in [kit ADR-0025](0025-the-kit-keeps-its-own-table-of-sessions.md).

## Considered Options

- **A remember token for each session, as kit ADR-0025 planned**: rejected. A session would expire after a short time, and a second token would bring it back. That is two ways to stay signed in, and each needs its own checks, its own swap of old cookies, and its own failed sign-in result. Large sites such as Google and GitHub keep one long-lived session instead.
- **A checkbox that picks a short session or a long session**: rejected. It keeps one session row, but it adds a choice that most users skip. A user on a shared computer signs out, and can end a session from the sessions list.
- **No maximum, only the idle limit**: rejected. A session that is used each day would never end, so a stolen session cookie would stay valid.

## Consequences

- `SESSION_LIFETIME` is the idle limit, in minutes, with a default of 43200 (30 days). `SESSION_MAX_DAYS` is the maximum from the sign-in, with a default of 90. An empty value means no maximum.
- `SESSION_EXPIRE_ON_CLOSE` stays `false`, so closing the browser does not end the session.
- The kit drops the `users.remember_token` column, and Laravel never reads or writes a remember token.
- A remember cookie from before this decision signs nobody in. A user whose old session expired signs in once more.
- The sign-in log has no "remember cookie refused" result. This replaces that point in [kit ADR-0026](0026-one-sign-in-log-records-who-used-which-account.md).
- A sign-in on a shared computer stays signed in until the user signs out, or until the session is ended or expires. An app that needs short sessions sets `SESSION_LIFETIME` lower.
