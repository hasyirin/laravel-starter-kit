# Sessions

A session is one signed-in browser. A user can have many sessions, for example on a phone and on a laptop. The kit keeps one row for each session in its own table, so users and staff can list and end sessions. See [kit ADR-0025](adr/kit/0025-the-kit-keeps-its-own-table-of-sessions.md), [kit ADR-0027](adr/kit/0027-sessions-are-long-lived-and-the-kit-has-no-remember-me.md), and [kit ADR-0024](adr/kit/0024-a-session-belongs-to-the-person-at-the-browser.md).

## Lifetime

Sessions are long-lived, and the kit has no "remember me". The login page has no checkbox, and the kit sets no remember cookie. Each sign-in starts one session. Two limits end it:

| Limit | Setting | Default |
| --- | --- | --- |
| Idle limit: days with no request | `SESSION_LIFETIME`, in minutes | 43200 (30 days) |
| Maximum: days from the sign-in | `SESSION_MAX_DAYS`, read as `system.sessions.max_days` | 90 |

If you set `SESSION_MAX_DAYS` to an empty value, sessions have no maximum. A value that is not a whole number of 1 or more also means no maximum. `RemoveExpiredSessions` writes a warning to the log in this case.

`SESSION_EXPIRE_ON_CLOSE` stays `false`, so closing the browser does not end a session. An app that needs short sessions sets a lower `SESSION_LIFETIME` and `SESSION_MAX_DAYS`.

The kit dropped the remember token column of users. `User::getRememberTokenName()` returns an empty string, so Laravel never reads or writes a remember token. At each sign-in, `ForgetRememberCookie` expires an old remember cookie.

## The User Sessions Table

`user_sessions` holds one row for each session, for every session driver. This includes the database, Redis, and file drivers. A row has:

- `user_id` and `impersonator_id`.
- `session_id`, the id that the session store uses. `UserSession` hides it.
- `ip_address` and `user_agent` of the last write.
- `signed_in_at` and `last_activity_at`.

The model is `UserSession`. The row is not in the audit log, because the sign-in log records the sessions.

## How Session Tracking Works

`bootstrap/app.php` appends `TrackSession` to the `web` middleware group. On each request of a signed-in user, it does these steps:

Before the request, if the session has a row id:

1. If the row is gone, it signs the session out. This is how an ended session stops, also when the session store still holds it.
2. If the row is past `SESSION_MAX_DAYS`, it ends the row with `ExpireSession` and signs the session out.

When the app has made the response:

3. If the session has no row yet, it creates the row. A session that started before the table existed gets its row at its next request.
4. Otherwise, it updates the row. It writes the last activity, the IP address, and the user agent at most once each minute (`UserSession::WRITE_INTERVAL_SECONDS`), so a busy session does not write on each request.

Like `auth.session`, `TrackSession` signs out with `logoutCurrentDevice()`, so it writes no "sign-out" entry. The entry of `ExpireSession` records the end instead.

When a user signs in, `ForgetPreviousSessionOnLogin` deletes the row of the session before it. When a user signs out, `DeleteSessionOnSignOut` deletes the row.

## Impersonation

During an impersonation, the session belongs to the impersonator, not to the impersonated user. The row keeps the impersonator in `impersonator_id`.

- The impersonator sees the session in their own list. It carries the name of the impersonated user.
- The impersonated user does not see the session.
- If staff end all sessions of the impersonated user, the impersonation continues.
- If the impersonator ends their other sessions, the impersonation ends when it runs in another browser, because the current session stays. An action that ends all their sessions ends it too.
- If someone deletes the impersonated user, the session ends too. `DeleteUser` and `DeleteOtherUser` do this.

## End Sessions

Three Action classes in `app/Domain/User/Actions/Session` end sessions:

- `EndSession` ends one session. It writes a "session ended" entry, deletes the row, and destroys the session in the session store after the commit.
- `EndAllSessions` ends all sessions of a user with `EndSession`. It can leave one session out. With `includeImpersonated`, it also ends the sessions where someone impersonates the user.
- `ExpireSession` does the same as `EndSession` for a session that reached a limit. It writes a "session expired" entry, with no person who ended it.

### By People

- **Users** see their sessions in the Security tab of the settings page. They end one session, or all other sessions. The current session has no End button.
- **Staff** with `internal.controls.users.update-sessions` see the sessions of another user in the Sessions tab of the user page. They end one session, or all sessions of that user.

In both lists, End and End all ask for the password first. The list shows only sessions that are not expired. See [Routes and Access Areas](routing.md#settings-page).

### By the App

These changes end the sessions at once, with `EndAllSessions`:

| Change | Sessions that end |
| --- | --- |
| A user changes their own password (`UpdatePassword`). | All sessions of the user, except the current one. |
| Staff change the password of a user (`UpdateUser`). | All sessions of the user, except the current one. |
| A user resets a forgotten password (`ResetForgottenPassword`). | All sessions of the user. |
| A user deletes their account (`DeleteUser`). | All sessions of the user, and all sessions that impersonate the user. |
| Staff delete a user (`DeleteOtherUser`). | All sessions of the user, and all sessions that impersonate the user. |

The `auth.session` middleware stays in the `web` group as a backstop. See [Routes and Access Areas](routing.md).

## Expiry and the Daily Job

The session store drops an idle session after `SESSION_LIFETIME`. The row stays until `RemoveExpiredSessions` runs. It runs each day (`routes/console.php`). It handles a row that is idle for `SESSION_LIFETIME` plus one minute, or that is past `SESSION_MAX_DAYS`. For each row, it calls `ExpireSession`. The lists of sessions hide these rows before the job runs.

## Sign-in Log

The sign-in log records the end of each session. See [Sign-in Log](sign-in-log.md).

- "Session ended": a person or an Action class ended the session. `sign_in_entries.ended_by_id` names who did it. It can be empty when nobody is signed in, for example when `DeleteOtherUser` runs in a job or a console command. `ResetForgottenPassword` records the user as the one who ended it.
- "Session expired": the session reached a limit. `sign_in_entries.ended_by_id` is empty.

A normal sign-out makes a "sign-out" entry, not a "session ended" entry.

## Guest Sessions

A guest session lasts as long as any other session, so 30 days by default. Each visitor and each bot that opens a page makes one. The session store holds more guest sessions than with a 2-hour lifetime. Plan the memory of Redis, or the size of the `sessions` table, for this. A guest session has no row in `user_sessions`.

## Existing Apps

An app that pulls this change needs these steps:

1. Run the migrations. They drop the remember token column of users, create `user_sessions`, and add `sign_in_entries.ended_by_id`.
2. Check `.env` for `SESSION_LIFETIME`. The old kit default was 120. If the file has `SESSION_LIFETIME=120`, set 43200 or remove the line. Otherwise each session ends after 2 idle hours.
3. Add `SESSION_MAX_DAYS` to `.env` if you want a maximum other than 90 days.
4. Know that users who are signed in at the upgrade stay signed in. Their session gets a row at its next request, if the session has not expired yet. The response to that request gives the session cookie the new lifetime. Redis keeps the session for the new lifetime from that write. The file and database drivers check the idle time against the current `SESSION_LIFETIME` each time they read the session. For these sessions, `signed_in_at` is the time of that first request, so the maximum days count from the upgrade.
5. Know that only a user who relied on a "remember me" cookie signs in again, once, after the session of the browser expires. The old cookie signs nobody in. The next sign-in expires it.
6. Make sure the scheduler runs, so `RemoveExpiredSessions` runs each day.
