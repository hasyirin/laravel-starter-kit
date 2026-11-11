# Only a Signed-In Recipient Makes a View

Staff need proof that a recipient read an email, but a pixel or a link fetch cannot give it. Apple Mail Privacy Protection and Gmail load every image on their own. Safe Links, Mimecast, and similar scanners follow every link when an email arrives. So the mail log keeps three signals. An open comes from the pixel, and a click comes from a link to a public page or another site. Neither is proof. A view comes only when the recipient, signed in, follows a link to a page that needs a sign-in.

## Considered Options

- Record a view when anyone fetches a link. Rejected: scanners would mark almost every email to a government or company address as viewed when it arrives.
- Track opens with the pixel only. Rejected: it gives false opens from image proxies, and it misses opens when Outlook blocks images.

## Consequences

- An email to a recipient without an account can never have a view. It can have only an open or a click.
- An email that has no link to a page behind a sign-in can never have a view.
- The log stores no IP address and no user agent. They show the proxy or the scanner, not the person, and they are personal data under the PDPA.
