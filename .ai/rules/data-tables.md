---
paths:
  - 'app/Livewire/DataTables/**'
---

# Data Tables

## Show yes or no with a boolean column, and more with a badge
If a column shows only yes or no, use `IconColumn::make('is_owner')->boolean()`, labelled with the property's name, for example "Owner". Do not turn it into a text badge column with a `state()` that returns a label or null.

If a column shows more than yes or no, use a text badge column. Examples are a status, a kind, and a count. The Resend column of the message log is one: it shows "Resend", "Resent 2 times", or both.
