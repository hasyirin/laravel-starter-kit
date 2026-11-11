# Letters Are Kit Records That Print Declared Letter Data

A letter is a row in a kit table, linked to its letter source, such as an approval or a notice. The app does not add letter columns to its own models. So each app gets the same list, preview, print, numbering, and delete steps without building them again.

A letter type maps its letter source to declared letter data, and its view prints only that data. A view never runs a query or calls a model, and it makes no decisions: it only checks whether a value is filled or yes. The mapping does every comparison and calculation. So a system administrator can make a manual letter: load the letter data, change any value, and print it, without a change to the records.

## Considered Options

- **Letter columns on each app model**: rejected. Earlier apps did this, for example a letter number, letter data, and a signer on an order. Each app then built its own letter steps, and a second kind of letter needed the same columns again.
- **Views that read models directly**: rejected. Earlier apps did this, and some views ran queries. Nobody could list the values that a view printed, so a wrong value needed a data fix or a change to the code.

## Consequences

- Each letter type writes a mapping from its source to its letter data. A long letter can need 20 or more values, including lists and yes/no values for its optional parts.
- An app letter runs its mapping again at each print, so a fix to its letter source shows on the next print. A mapping compares dates with the letter date, never with today, so a reprint makes the same choices.
- Text that is fixed in a view is not letter data. A change to that text is a change to the letter type.
