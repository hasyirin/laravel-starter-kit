# Impersonation Keeps the Impersonated User as the Actor

During impersonation, the signed-in user is the impersonated user. So the actor of each action, and the user on each audit entry, is the impersonated user, as [kit ADR-0003](0003-user-is-the-actor-with-a-profile-snapshot.md) says. Each audit entry also stores the impersonator's id in its properties. The id comes from the context, so an audit entry from a queued job also gets it.

## Considered Options

- **Make the impersonator the actor**: rejected. The record would then show that the impersonator did the work on their own account. The page would also look different from what the impersonated user sees.
- **Block all changes during impersonation**: rejected. Staff impersonate to see and to fix what a user sees.
- **Record an audit entry when impersonation starts and stops**: rejected. A session can expire or end without a stop. Then the log shows a start with no stop, and nobody knows which changes the impersonator made.

## Consequences

- To find what an impersonator did, search the audit entries for their id in the properties, not for the user on the entry.
- Actions do not store the impersonator. An action shows the impersonated user and their profile.
