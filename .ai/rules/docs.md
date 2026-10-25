---
paths:
  - 'docs/**'
---

# Docs

## Never edit a generated section
`php artisan docs:generate` writes the text between `<!-- generated:name -->` and `<!-- /generated:name -->` from the code. To change that text, change the code and run the command. Write only outside these markers.

## Put only real code names in backticks
Write a code name in backticks: a file path, a class, `Class::method()`, a permission, a route name, a config key, a `table.column`, or an environment variable. `composer docs:check` fails when such a name does not exist. Leave out the backticks for a word that is not a code name.
