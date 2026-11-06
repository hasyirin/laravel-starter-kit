# Starter Kit

The shared language of the kit and of each app built from it. An app adds its own terms under a heading of its own.

## Language

### Users

**User**:
A person who signs in to the app. A user can have any number of profiles.

**User information**:
A user's name and email.
_Avoid_: Account, account details, profile, profile information, user details

**Preferences**:
A user's time zone and locale.

**Profile**:
A snapshot of a user's placement, roles, and permissions at one point in time.

**Active profile**:
A user's newest profile. Its placement, roles, and permissions apply now.
_Avoid_: Current profile, latest profile

**Placement**:
The organization, branch, department, and position on a profile.

### Organizations

**Organization**:
A company, agency, or other body that users belong to.

**Department**:
A unit of an organization that does one kind of work, for example finance.

**Branch**:
A place where an organization works, for example its headquarters.

**Position**:
A job in an organization, for example clerk. Many users can hold one position.

**Owner**:
The organization that runs the app.
_Avoid_: Local organization, default organization

**Owner department**:
The owner's department that runs the app, for example its IT department.

**Owner branch**:
The branch where the owner is based, for example its headquarters.

### Access

**Area**:
A part of the app with its own pages and its own access rule: guest, app, internal, or admin.

**Staff**:
The users who can open the internal area.

**System administrator**:
A user who can open the admin area.
_Avoid_: Administrator, admin

**Public user**:
A user who can open neither the internal area nor the admin area.
_Avoid_: Guest

**Permission**:
The right to open an area or to do one thing in the app, for example view a role.

**Bypass**:
The permission that passes every access check.

**Role**:
A named set of permissions that a profile can hold. A change to a role applies at once to every profile that holds it.

**System role**:
A role that comes with the app. Nobody can rename or delete it.

**Direct grant**:
A role or permission that is given to a user, not to a profile.
_Avoid_: Direct permission

**Impersonation**:
Use of the app as another user, without that user's password.
_Avoid_: Login as, switch user, sudo

**Impersonator**:
The user who impersonates another user.

**Impersonated user**:
The user whom an impersonator uses the app as.

### Actions

**Action**:
One thing that a user did to a record, such as prepare or approve it. It holds the user, the time, and the profile that was active then.

**Actor**:
The user who did an action.

**Preparer**:
The user who created a record.

### Workflows

**Workflow**:
The states that a record moves through, and the steps between them.

**State**:
The point that a record has reached in a workflow, for example site visit.

**Step**:
A move of a record from one state to another.
_Avoid_: Transition

**Loop**:
A step back to an earlier state.

### Audit

**Audit log**:
The record of every change to the app's records, with who made each change and when.
_Avoid_: Activity, activity log, history

**Audit entry**:
One change in the audit log: the record, the event, the user who made it, and the old and new values.
_Avoid_: Activity, log entry
