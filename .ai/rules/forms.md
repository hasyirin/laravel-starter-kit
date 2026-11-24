---
paths:
  - 'app/Livewire/Forms/**'
---

# Forms

## Order form methods set, rules, others, save
Order the methods of a form like this: `set()`, then `rules()`, then any other methods, then `save()` last.

## Name each validated field
Give each field in `rules()` a name in `attributes` of `lang/en/validation.php` and of every other language. Add `validationAttributes()` to a form only for a key that means another thing in another form, such as `type`. Write such a name as it reads inside a sentence, as `.ai/rules/lang.md` says. For a label with no capital after the first letter, `str(__('letters/letters.type'))->lower()->value()` is enough.
