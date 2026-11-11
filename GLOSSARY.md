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
A job in an organization, for example clerk. A position can belong to one department, and that department never changes. Many users can hold one position.

**Owner**:
The organization that runs the app.
_Avoid_: Local organization, default organization

**Owner department**:
The owner's department that runs the app, for example its IT department.

**Owner branch**:
The branch where the owner is based, for example its headquarters.

### Access

**Area**:
A part of the app with its own pages and its own access rule: guest, app, internal, or system.

**System area**:
The area with the tools that run the app, such as the queue dashboard.
_Avoid_: Admin area, admin panel

**Staff**:
The users who can open the internal area.

**System administrator**:
A user who can open the system area.
_Avoid_: Administrator, admin

**Public user**:
A user who can open neither the internal area nor the system area.
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
_Avoid_: Action class, which is the code's name for one operation

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

### Addresses

**Address**:
The postal address of a record: up to three lines, a postcode, a city, and the state of that city. A record has at most one address of each type, such as primary and mailing.

**City**:
The post office that delivers the mail to an address. It is the name after the postcode, as on a MyKad. The code calls it a post office.
_Avoid_: Town

**Location**:
The point of an address on the map, as a latitude and a longitude.
_Avoid_: Coordinates, lat lng, pin, geolocation

**Subdistrict**:
A part of a district, called a mukim in Malay. The bundled data has subdistricts only for Negeri Sembilan and Kemaman.
_Avoid_: Mukim

**Geography**:
The countries, states, districts, subdistricts, cities, and postcodes that an address can use.
_Avoid_: Geographic data, places, locations

**Locality**:
The country where the app works, with its state and district when the app sets them. A geography record is local when it is the locality, contains it, or is in it. A city is local when it is in the locality's state, because a city is not part of a district.
_Avoid_: Home state, app's state

**Local area**:
The post offices whose addresses a form accepts when the form has the local limit. By default, they are the post offices of the locality's state.
_Avoid_: Service area, jurisdiction

### Messages

**Email**:
A message that the app sends to an email address. The code calls it a mail.

**Recipient**:
The one person in the To field of an email.
_Avoid_: Addressee

**Copy recipient**:
A person in the CC or BCC field of an email.
_Avoid_: CC, BCC, which are the fields, not the people

**Mail log**:
The record of every email that the app sends, with its recipient, its status, and the record that it is about.
_Avoid_: Email log, outbox, sent items

**Sent**:
The status of an email that the mail server accepted. It is not proof that the email reached the inbox.
_Avoid_: Delivered, successful

**Notification**:
A message that the app shows to a user inside the app, under the bell.
_Avoid_: Alert, in-app message

**Open**:
Each time that the email client of a recipient loads an email's images. It is not proof that a person read the email.
_Avoid_: Read

**Click**:
The first time that anyone follows a link in an email to a public page or another site. Link scanners can cause it, so it is not proof that a person read the email.

**View**:
The first time that the recipient, signed in, follows a link in an email to a page that needs a sign-in. It is the only proof that the recipient read the email.
_Avoid_: Open, download, read receipt

**Resend**:
A new email that sends an earlier email again, to its recipient or to another address. It links to the email that it repeats.
_Avoid_: Retry, which is another try of the same email after a failure
