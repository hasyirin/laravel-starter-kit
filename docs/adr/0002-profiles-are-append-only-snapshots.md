# Profiles Are Append-Only Snapshots

A profile records a user's placement, roles, and permissions at one point in time. Other records show the profile that was active then, for example the position on an act. So the kit never changes a profile: a change creates a new profile, and the newest one is active. See **Profiles** in the README for the rules.

## Considered Options

- **A stored `ended_at` column**: rejected. It gives plain reads and can end a profile without a successor, but each change needs an insert and an update. `Profile::withEndedAt()` derives the end from the next profile's creation time, so each change stays one insert. To end a profile without a successor, create a profile with no placement and no roles.
- **Spatie's deferred role assignment**: rejected. For an unsaved model, Spatie attaches the roles in a `saved` listener. `DatabaseSeeder` turns model events off, so seeded profiles lost their roles without an error. `HasFrozenRoles` attaches them in its own `save()` instead.
- **A cascade when a role is deleted**: rejected, because it would change old profiles. `Role::delete()` throws while a profile has the role.
