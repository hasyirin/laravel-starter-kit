# Starter Kit

The shared language of the kit and of each app built from it. An app adds its own terms under a heading of its own. The `_Malay_` line of a term is its one name in the Malay text of the app.

## Language

### Users

**User**:
A person who signs in to the app. A user can have any number of profiles.
_Malay_: Pengguna

**User information**:
A user's name, email, and phone number.
_Avoid_: Account, account details, profile, profile information, user details
_Malay_: Maklumat pengguna

**Phone number**:
The mobile number where a user gets SMS. A user has at most one.
_Avoid_: Mobile number, handphone number, HP number, contact number
_Malay_: Nombor telefon

**SMS code**:
A one-time code that the app sends by SMS. A user types it to prove that they hold the phone number.
_Avoid_: OTP, TAC, PIN
_Malay_: Kod SMS

**Preferences**:
A user's time zone, language, and locale.
_Malay_: Pilihan

**Language**:
The language of the text that the app shows to a user, for example English or Malay.
_Avoid_: Locale, which is the date and time format
_Malay_: Bahasa

**Locale**:
The format of the dates and times that the app shows to a user, for example `en-MY`. It does not set the language.
_Avoid_: Language, region
_Malay_: Format tarikh dan masa

**Profile**:
A snapshot of a user's placement, roles, and permissions at one point in time.
_Malay_: Profil

**Active profile**:
A user's newest profile. Its placement, roles, and permissions apply now.
_Avoid_: Current profile, latest profile
_Malay_: Profil aktif

**Placement**:
The organization, branch, department, and position on a profile.
_Malay_: Penempatan

### Organizations

**Organization**:
A company, agency, or other body that users belong to.
_Malay_: Organisasi

**Department**:
A unit of an organization that does one kind of work, for example finance.
_Malay_: Jabatan

**Branch**:
A place where an organization works, for example its headquarters.
_Malay_: Cawangan

**Position**:
A job in an organization, for example clerk. A position can belong to one department, and that department never changes. Many users can hold one position.
_Malay_: Jawatan

**Owner**:
The organization that runs the app.
_Avoid_: Local organization, default organization
_Malay_: Pemilik

**Owner department**:
The owner's department that runs the app, for example its IT department.
_Malay_: Jabatan pemilik

**Owner branch**:
The branch where the owner is based, for example its headquarters.
_Malay_: Cawangan pemilik

### Access

**Area**:
A part of the app with its own pages and its own access rule: guest, app, internal, or system.
_Malay_: Kawasan

**System area**:
The area with the tools that run the app, such as the queue dashboard.
_Avoid_: Admin area, admin panel
_Malay_: Kawasan sistem

**Staff**:
The users who can open the internal area.
_Malay_: Kakitangan

**System administrator**:
A user who can open the system area.
_Avoid_: Administrator, admin
_Malay_: Pentadbir sistem

**Public user**:
A user who can open neither the internal area nor the system area.
_Avoid_: Guest
_Malay_: Pengguna awam

**Permission**:
The right to open an area or to do one thing in the app, for example view a role.
_Malay_: Akses

**Bypass**:
The permission that passes every access check.
_Malay_: Akses penuh

**Role**:
A named set of permissions that a profile can hold. A change to a role applies at once to every profile that holds it.
_Malay_: Peranan

**System role**:
A role that comes with the app. Nobody can rename or delete it.
_Malay_: Peranan sistem

**Direct grant**:
A role or permission that is given to a user, not to a profile.
_Avoid_: Direct permission
_Malay_: Pemberian terus

**Impersonation**:
Use of the app as another user, without that user's password.
_Avoid_: Login as, switch user, sudo
_Malay_: Menyamar, and "Menyamar sebagai" before a name

**Impersonator**:
The user who impersonates another user.
_Malay_: Penyamar

**Impersonated user**:
The user whom an impersonator uses the app as.
_Malay_: Pengguna yang disamar

**Management**:
Work that a user does on records of other users, by permission. Examples: create a user, verify a user's email, approve an application. The opposite is work on your own records, such as a change to your preferences.
_Avoid_: Administration, admin; also internal or system for this, because those are areas, not kinds of work

### Actions

**Action**:
One thing that a user did to a record, such as prepare or approve it. It holds the user, the time, and the profile that was active then.
_Avoid_: Action class, which is the code's name for one operation
_Malay_: Tindakan

**Actor**:
The user who did an action.
_Malay_: Pelaku

**Preparer**:
The user who created a record.
_Malay_: Penyedia

### Workflows

**Workflow**:
The states that a record moves through, and the steps between them.
_Malay_: Aliran kerja

**State**:
The point that a record has reached in a workflow, for example site visit.
_Malay_: Status

**Step**:
A move of a record from one state to another.
_Avoid_: Transition
_Malay_: Langkah

**Loop**:
A step back to an earlier state.
_Malay_: Ulangan

### Audit

**Audit log**:
The record of every change to the app's records, with who made each change and when.
_Avoid_: Activity, activity log, history
_Malay_: Log audit

**Audit entry**:
One change in the audit log: the record, the event, the user who made it, and the old and new values.
_Avoid_: Activity, log entry
_Malay_: Entri audit

### Letters

**Letter**:
Signed correspondence from the owner to a recipient, with a reference number. A report or a bill is not a letter.
_Avoid_: Document, printable, surat
_Malay_: Surat

**Letter type**:
A kind of letter, for example a demolition notice or a permit approval. It sets the text, the layout, and the numbering of its letters.
_Avoid_: Template
_Malay_: Jenis surat

**Letter source**:
The record that a letter is about, for example an approval or a notice.
_Avoid_: Subject, which is the title line of a letter
_Malay_: Sumber surat

**Letter data**:
The named values that a letter prints, for example the recipient, the title, and the fees. Each letter type declares its letter data.
_Avoid_: Variables, placeholders
_Malay_: Data surat

**Manual letter**:
A letter that a system administrator makes from letter data typed or changed by hand, outside the letter records. It has no letter row and takes no number from a sequence.
_Avoid_: Hack, override
_Malay_: Surat manual

**Letterhead**:
The header image, and an optional footer image, that a letter prints for an organization. A letterhead can also be for one branch or one department of that organization.
_Avoid_: Kop surat, header
_Malay_: Kepala surat

**Carbon copy**:
A party who gets a copy of a letter. The letter lists its carbon copies after the signature.
_Avoid_: CC, s.k.
_Malay_: Salinan kepada

**Reference number**:
The number that identifies a letter. A letter gets it once, and it is never given to another letter.
_Avoid_: Letter number, file reference, Bil., No. Rujukan
_Malay_: Nombor rujukan

**Signer**:
The staff member whose name and position a letter prints above the signature line. Some letters have no signer, and a person signs them by hand.
_Malay_: Penandatangan

**Preview**:
A PDF of a letter that is marked as a draft and has no reference number yet. The app never keeps a preview.
_Avoid_: Draft print
_Malay_: Previu

**Print**:
One PDF of a letter, made at one time with one signer and one set of carbon copies.
_Avoid_: Copy, version
_Malay_: Cetakan, and "Cetak" for the action

**Kept print**:
A print that the app stores, with the user who made it and the time. Only some letters keep their prints.
_Avoid_: History, which the audit log means
_Malay_: Cetakan tersimpan

### Addresses

**Address**:
The postal address of a record: up to three lines, a postcode, a city, and the state of that city. A record has at most one address of each type, such as primary and mailing.
_Malay_: Alamat

**City**:
The post office that delivers the mail to an address. It is the name after the postcode, as on a MyKad. The code calls it a post office.
_Avoid_: Town
_Malay_: Bandar

**Location**:
The point of an address on the map, as a latitude and a longitude.
_Avoid_: Coordinates, lat lng, pin, geolocation
_Malay_: Lokasi

**Subdistrict**:
A part of a district, called a mukim in Malay. The bundled data has subdistricts only for Negeri Sembilan and Kemaman.
_Avoid_: Mukim
_Malay_: Mukim

**Geography**:
The countries, states, districts, subdistricts, cities, and postcodes that an address can use.
_Avoid_: Geographic data, places, locations
_Malay_: Geografi

**Locality**:
The country where the app works, with its state and district when the app sets them. A geography record is local when it is the locality, contains it, or is in it. A city is local when it is in the locality's state, because a city is not part of a district.
_Avoid_: Home state, app's state
_Malay_: Lokaliti

**Local area**:
The post offices whose addresses a form accepts when the form has the local limit. By default, they are the post offices of the locality's state.
_Avoid_: Service area, jurisdiction
_Malay_: Kawasan tempatan

### Messages

**Message**:
Something that the app sends to a person: an email, an SMS, or a notification.
_Avoid_: Notification, which is only the message under the bell
_Malay_: Mesej

**Email**:
A message that the app sends to an email address. The code calls it a mail.
_Malay_: E-mel

**SMS**:
A message that the app sends to a phone number.
_Malay_: SMS

**Channel**:
The way that a message reaches a person, for example email or SMS.
_Malay_: Saluran

**Recipient**:
The one person that a message is for. For an email, it is the person in the To field.
_Avoid_: Addressee
_Malay_: Penerima

**Copy recipient**:
A person in the CC or BCC field of an email.
_Avoid_: CC, BCC, which are the fields, not the people
_Malay_: Penerima salinan

**Message log**:
The record of every message that leaves the app, such as an email or an SMS. It holds the recipient, the status, and the record that the message is about. It does not hold notifications.
_Avoid_: Mail log, email log, SMS log, outbox, sent items
_Malay_: Log mesej

**Sent**:
The status of a message that the mail server or the SMS gateway accepted. It is not proof that the message reached the person.
_Avoid_: Delivered, successful
_Malay_: Dihantar

**Started**:
The time when the app started to send a message. For a message on the queue, it is the time of the first try.
_Avoid_: Created, queued
_Malay_: Dimulakan

**Notification**:
A message that the app shows to a user inside the app, under the bell. A notification class is the code's name for a class that sends a message of any kind.
_Avoid_: Alert, in-app message
_Malay_: Notifikasi

**Open**:
Each time that the email client of a recipient loads an email's images. It is not proof that a person read the email.
_Avoid_: Read
_Malay_: Dibuka

**Click**:
The first time that anyone follows a link in an email to a public page or another site. Link scanners can cause it, so it is not proof that a person read the email.
_Malay_: Diklik

**View**:
The first time that the recipient, signed in, follows a link in an email to a page that needs a sign-in. It is the only proof that the recipient read the email.
_Avoid_: Open, download, read receipt
_Malay_: Dilihat

**Resend**:
A new message that sends an earlier email or SMS again, to its recipient or to another email address or phone number. It links to the message that it repeats. The message log marks the earlier message as resent.
_Avoid_: Retry, which is another try of the same message after a failure
_Malay_: Hantar semula

**Intercept**:
A setting for servers outside production that keeps messages away from real people. The app sends each email and SMS to the signed-in user in place of the recipient. During impersonation, it sends them to the impersonator.
_Avoid_: Redirect, sandbox
_Malay_: Intercept
