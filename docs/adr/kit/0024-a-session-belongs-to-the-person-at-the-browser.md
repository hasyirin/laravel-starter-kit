# A Session Belongs to the Person at the Browser

During impersonation, the impersonator's browser signs in as the impersonated user. The session store then holds the session as the impersonated user's. The kit lists and ends that session as the impersonator's session, not as the impersonated user's.

This does not change [kit ADR-0008](0008-impersonation-keeps-the-impersonated-user-as-the-actor.md). The actor tells whose record the work changed. The session tells whose browser it is.

## Considered Options

- **List the session under the impersonated user, as the session store holds it**: rejected. The impersonated user would see a session from the impersonator's IP address, and could end it. The impersonator could not end it from their own list. If someone steals the impersonator's account, a password change would not end that session.

## Consequences

- The kit records the impersonator on each session during impersonation.
- The impersonator sees the session in their own list, marked with the impersonated user's name.
- The impersonated user does not see the session. When staff end all of the impersonated user's sessions, the impersonation continues.
- When the impersonator ends their other sessions, or their sessions are ended by management, the impersonation ends.
- When a user is deleted, each session where someone impersonates that user ends too, because the impersonation cannot continue without the user.
