# The Kit Keeps Its Own Table of Sessions

The kit keeps one table of sessions for every session driver. Each row is one session, from the sign-in until it signs out, is ended, or expires. A session that "remember me" keeps alive stays one row, though Laravel gives it a new session id each time the old one expires. The row holds the remember token of that session, so an ended session cannot sign in again. To end a session, the kit destroys its current session id through the driver's handler and deletes the row.

## Considered Options

- **Read Laravel's `sessions` table, and support only the database driver**: rejected. The kit's apps use Redis too.
- **Use the storage of each driver**: rejected. Laravel's `sessions` table holds the impersonated user during impersonation, which goes against [kit ADR-0024](0024-a-session-belongs-to-the-person-at-the-browser.md). Laravel deletes its row when the session expires, but a remember token must live longer. Redis and file storage cannot find the sessions of one user without a scan of all of them. So each driver would need data of the kit anyway, in two implementations.
- **One remember token for each user, as Laravel has**: rejected. To end one session, the kit would have to change the token of the user. Then each other remembered browser must sign in again.

## Consequences

- The list of sessions works the same way with the database, Redis, and file drivers.
- The kit replaces Laravel's remember token check with a check against the token on the session row.
- A daily job deletes the rows of expired sessions.
- A session that started before this table existed gets its row at its next request. An old remember cookie is swapped for one with the token of its session, so no old cookie stays out of the list.
- Laravel's `sessions` table stays only as the storage of the database driver.
