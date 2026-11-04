# The User Is the Actor, with a Profile Snapshot

An action records the signed-in user as its actor, as laravel-actor does by default. When the action is saved, `ActionObserver` also stores the user's active profile with it. So an act shows the position that the user held at that time, even after the user gets a new profile. We did not make the profile the actor, because a user can act without a profile, for example right after registration. See `docs/components/act.md`.
