---
paths:
  - 'app/Livewire/DataTables/**'
---

# Data Tables

## Show boolean properties with a boolean column
Show a boolean property in a table with `IconColumn::make('is_owner')->boolean()`, labelled with the property's name, for example "Owner". Do not turn it into a text badge column with a `state()` that returns a label or null.
