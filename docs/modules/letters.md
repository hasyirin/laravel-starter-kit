# Letters

The kit makes PDF letters. A letter type maps a letter source to declared letter data. A Blade view prints that data. Headless Chrome turns the view into a PDF. See `docs/adr/0010-letters-render-from-blade-with-headless-chrome.md` and `docs/adr/0011-letters-are-kit-records-that-print-declared-letter-data.md` for the reasons.

A letter is signed correspondence with a reference number. A report or a bill is not a letter. For these, see **Render a Report or a Bill**.

## Add a Letter Type

The example is the approval of a permit. The letter source is the `Permit` model.

### 1. Add HasLetters to the Source

```php
use App\Domain\Shared\Letter\Concerns\HasLetters;

class Permit extends Model
{
    use HasLetters;
}
```

`HasLetters` gives the source a `letters()` relation. The source needs a policy with the abilities that `sourceAbility()` returns. See **Access**.

### 2. Write the Letter Type

A letter type extends `App\Domain\Shared\Letter\Support\LetterType`. Four methods are required.

| Method     | What it returns                                                            |
| ---------- | -------------------------------------------------------------------------- |
| `view()`   | The name of the Blade view.                                                |
| `fields()` | The list of `Field` objects. They declare all the letter data.             |
| `source()` | The class of the letter source.                                            |
| `data()`   | The values of the letter data, made from the source and the letter date.   |

```php
class PermitApprovalLetter extends LetterType
{
    public function view(): string
    {
        return 'letters.permit-approval';
    }

    public function source(): string
    {
        return Permit::class;
    }

    public function fields(): array
    {
        return [
            Field::text('applicantName'),
            Field::date('approvedOn'),
            Field::number('fee'),
            Field::group('conditions', 'Conditions', [
                Field::lines('summary'),
                Field::list('deadlines')->optional(),
            ]),
        ];
    }

    public function data(Model $source, CarbonImmutable $date): array
    {
        $hasConditions = $source->conditions !== null;

        return [
            'applicantName' => $source->applicant->name,
            'approvedOn' => $source->approved_at->toDateString(),
            'fee' => $source->fee,
            'conditions' => $hasConditions ? [
                'summary' => $source->conditions,
                'deadlines' => $source->deadlines,
            ] : null,
        ];
    }
}
```

The field types are text, lines, date, number, yes or no, choice, list, table, image, and group. Make a field optional with `optional()`. Set its label with `label()`. A group holds child fields. A group that has no filled child counts as absent.

The mapping makes every decision. It compares dates with `$date`, never with today, so a reprint makes the same choices. Fixed text belongs to the view. A change to fixed text is a change to the letter type.

#### Strict Mapping

`data()` must return every declared key. Return null for an empty optional field. If `data()` returns an undeclared key or misses a declared key, `LetterType::letterData()` throws a `LogicException` that names the letter type and the key. The values also pass the validation rules of the fields.

An image field holds the uuid of a raster image (PNG, JPEG, WebP, or GIF) that belongs to the letter source. A table field cannot have an image column.

#### Other Settings

Override these methods only when the letter type needs them.

| Method                   | Default                 | Meaning                                                                                          |
| ------------------------ | ----------------------- | ------------------------------------------------------------------------------------------------ |
| `label()`                | the headline of the key | The name of the letter type in the letter form, the letters table, and the compose page.         |
| `locale()`               | `system.locale`         | The locale of the text and of the date.                                                          |
| `dateFormat()`           | `d MMMM y`              | The ICU format of the letter date.                                                               |
| `signer()`               | optional                | `SignerRequirement::Required`, `SignerRequirement::Optional`, or `SignerRequirement::Hidden`.    |
| `signers()`              | all users               | Narrows the users who can sign.                                                                  |
| `allowedFor()`           | everyone                | Says which printers may use the letter type. See [Access](#access).                              |
| `keepsPrints()`          | false                   | If true, each print is stored as a kept print.                                                   |
| `letterhead()`           | none                    | A fixed `Letterhead` or `LetterheadView`. It wins over the stored letterheads.                   |
| `offersLetterhead()`     | true                    | Shows the letterhead switch in the letter form.                                                  |
| `offersComputerSigned()` | false                   | Shows the computer-signed switch in the letter form.                                             |
| `lockedFields()`         | none                    | Names of extra fields and form parts that the user cannot change.                                |
| `extraFields()`          | none                    | Fields that the user types for one letter, for example a remark. The view gets them as `$extra`. |

A new letter gets today as its date, in `system.timezone`. A letter printed without a date also uses today in `system.timezone`.

### 3. Write the View

The view only prints and checks whether a value is filled. It runs no query, calls no model, and compares nothing. It uses no `{!! !!}`.

Every letter view gets three values.

| Value     | Holds                                                                               |
| --------- | ----------------------------------------------------------------------------------- |
| `$data`   | The letter data of `fields()`. A group is null when absent. A date is a Carbon date. |
| `$extra`  | The letter data of `extraFields()`.                                                 |
| `$letter` | The reference number, the date, the signer, the carbon copies, and the letterhead.  |

```blade
<x-letter :letter="$letter">
    <x-letter.recipient>{{ $data->applicantName }}</x-letter.recipient>
    <x-letter.subject>Approval of permit</x-letter.subject>

    <x-letter.item>The fee is {{ $data->fee }}.</x-letter.item>

    @if ($data->conditions)
        <x-letter.item>{{ $data->conditions->summary }}</x-letter.item>
        @foreach ($data->conditions->deadlines as $deadline)
            <x-letter.item>{{ $deadline }}</x-letter.item>
        @endforeach
    @endif

    <x-letter.signature :letter="$letter" />
    <x-letter.carbon-copies :letter="$letter" />
</x-letter>
```

Put the view in the folder resources/views/letters. The letter stylesheet `resources/css/letter.css` scans this folder for Tailwind classes. Run `bun run build` after you add a view or a class.

The letter parts are Blade components:

| Component                  | Prints                                                           |
| -------------------------- | ---------------------------------------------------------------- |
| `<x-letter>`               | The page, the reference number, and the date. Takes `:letter`.   |
| `<x-letter.recipient>`     | The recipient block.                                             |
| `<x-letter.subject>`       | The subject line in bold capitals.                               |
| `<x-letter.item>`          | One numbered paragraph.                                          |
| `<x-letter.signature>`     | The signer name and position, or space for a hand signature.     |
| `<x-letter.carbon-copies>` | The list of carbon copies.                                       |

The draft mark on a preview comes from a hidden context key that `RenderPdf` sets. The view needs no flag.

### 4. Register the Letter Type

Add the class to `system.letters.types` in `config/system.php`. The key is the name of the letter type. It is stored with each letter.

```php
'letters' => [
    'types' => [
        'permit-approval' => PermitApprovalLetter::class,
    ],
],
```

### 5. Drop in the Letters Table

Put one tag on the page of the source.

```blade
<livewire:data-tables.letter-data-table :source="$permit" />
```

The table renders its own letter modal. If two tables share a page, each table talks only to the modal of its own source. See [Letter Data Table](../components/letter-data-table.md). The modal uses [Letter Data Fields](../components/letter.data-fields.md).

## Numbering

`numbering()` returns a `NumberingMode`.

| Mode                                   | Behavior                                                            |
| -------------------------------------- | ------------------------------------------------------------------- |
| `NumberingMode::Manual`                | The user types the reference number. The default.                   |
| `NumberingMode::Automatic`             | The app gives the next number at the first print.                   |
| `NumberingMode::AutomaticWithOverride` | Like automatic numbering. The user can also type a sequence number. |

`reference(int $number, Letter $letter)` turns the number into the reference number. The default returns the number as text.

### Sequence Keys

`sequenceKey()` returns a list of parts. The parts make the key of the sequence. The default is the key of the letter type and the organization of the signer. Without a signer, the key uses the organization of the printer. Each key has its own count.

To share one count between letter types, return the same parts from both. For example, a per-file `Bil.` returns the file id as a part.

A part must be a string or an integer. `ReferenceSequence::key()` refuses any other part with a `LogicException`. If the default key finds no organization, the letter gets no number. This happens when the letter has no signer and the printer has no organization. The print then fails with the `letters/letters.organization_required` message.

The letter stores its key at the first number. The key does not change later, even if the signer changes.

### Resets

`sequenceReset()` returns a `Hasyirin\RunningNumber\Enums\ResetInterval`. The default is `NEVER`. With `YEARLY`, the count starts again at 1 each year.

A reset type must put the period in `reference()`. If two letters in one key get the same reference number, `AssignReferenceNumber` throws a `LogicException`. This is how numbering stops when `reference()` leaves out the period. The transaction rolls back, so no number is lost.

The period is the day the number is issued, not the letter date. A backdated letter shows the year of the issue day. To use the year of the letter date, put that year in the sequence key and keep `NEVER`.

```php
public function sequenceReset(): ResetInterval
{
    return ResetInterval::YEARLY;
}

public function reference(int $number, Letter $letter): string
{
    $year = CarbonImmutable::now(config()->string('system.timezone'))->year;

    return "PA/{$number}/{$year}";
}
```

### Rules for Numbers

- A number is never given again. A deleted letter keeps its number, and the next letter gets the next number.
- A number that the user types is skipped by the counter.
- The app does not release an override number.
- Two printers at the same time get two numbers. The count is locked in a transaction. SQLite ignores the lock, so test the lock on PostgreSQL.
- If Chrome fails after the number is given, the letter keeps its number. A retry uses it.
- `app.timezone` must stay `UTC`. The running number package stores `last_reset_at` with it. The resets use `system.timezone`.

## Letterheads

A letterhead is a header image and an optional footer image for an organization. It can also be for one branch or one department of the organization.

Staff with the `internal.controls.letterheads.create` permission manage letterheads on the Letterheads tab of the organization page, in internal controls.

- Each image must be at least 2480 pixels wide (A4 at 300 ppi). The height is free.
- An image letterhead prints on page 1 only. A `LetterheadView` can print on every page.
- The letter form has a letterhead switch. The letter stores the choice.

### Lookup Order

`ResolveLetterhead` takes the first match:

1. The `letterhead()` of the letter type.
2. The best letterhead for the placement of the signer.
3. The best letterhead for the placement of the printer.

For one placement, a letterhead for a branch wins over a letterhead for a department. A letterhead for both wins over both. A letterhead for the whole organization is the last choice.

### Write a Custom Letterhead View

Return a `LetterheadView` from `letterhead()` when an image is not enough.

```php
public function letterhead(): LetterheadView
{
    return new LetterheadView('letters.office-letterhead', heightMm: 40, everyPage: true);
}
```

The view prints HTML for the header. `heightMm` is the height that the page reserves. If `everyPage` is false, the header prints on page 1 only.

To build a page yourself, use `<x-letter.page>` and its slots.

```blade
<x-letter.page :header-height="40" :footer-height="20">
    <x-slot:header>
        <p>Office</p>
    </x-slot:header>

    <x-slot:footer>
        <p>Footer</p>
    </x-slot:footer>

    {{ $slot }}
</x-letter.page>
```

| Slot or attribute            | Meaning                                                           |
| ---------------------------- | ----------------------------------------------------------------- |
| `header`, `footer`           | Print on every page.                                              |
| `firstHeader`, `firstFooter` | Print on page 1 only.                                             |
| `src` on a slot              | The slot is an image, and the page draws it as a CSS background.  |
| `header-height`, `first-header-height`, `footer-height` | The height in mm that the page reserves. |

Follow these limits:

- An image slot becomes a CSS background in the page margin. A letter of 300 ppi keeps its sharpness.
- An HTML header is a fixed element at the top of every page. The page adds spacer rows so that text does not run under it.
- A letter has one HTML header. An HTML `firstHeader` with an HTML `header` throws a `LogicException`.
- A page-1-only footer must be an image. An HTML `firstFooter` throws a `LogicException`.

## Render a Report or a Bill

A report or a bill is not a letter. It has no letter row, no reference number, and no signer. Render it with `RenderPdf` and print the letterhead with `<x-letterhead :placement>`.

```php
$pdf = RenderPdf::make()->execute('reports.sales', ['sales' => $sales], Preferences::current());

return $pdf->download('sales.pdf');
```

```blade
<x-letterhead :placement="$placement">
    <h1>Sales</h1>
</x-letterhead>
```

`$placement` is a `Placement`, for example `Placement::of($user->profile)`. The component finds the best letterhead for it. `RenderPdf` takes the view, the view data, the preferences (timezone and locale), and an optional draft flag.

## Access

`LetterPolicy` asks the policy of the letter source. It does not check a permission of its own. `LetterType::sourceAbility()` maps each letter ability to an ability on the source.

| Letter ability                  | Source ability |
| ------------------------------- | -------------- |
| `view`, `print`                 | `view`         |
| `create`, `update`, `delete`    | `update`       |

A letter type can override `sourceAbility()`. For example, it can return `archive` for `create`.

`LetterPolicy` also asks `LetterType::allowedFor()` for `print`, `create`, and `update`. A user that the letter type does not allow can still view the letter. That user cannot print it, preview it, upload its signed scan, or change it. The letters table hides these actions.

`allowedFor()` limits who may use a letter type. It does not stop the cleanup of old letters. So `delete` needs only the source ability. If a letter type later refuses everyone, its letters can still be deleted.

`LetterPolicy` and `AuditEntryPolicy` are two stated exceptions to the rule that a policy extends `Policy` and checks permissions with `hasPermission()`.

- `LetterPolicy` has no permissions. The source decides, so each app controls its letters through the policy of its source.
- `AuditEntryPolicy` reads `system.audit-log.view` directly. `Policy::path()` builds plural names, and an audit entry has no plural permission path.

In the letter form:

- The print choices (signer, carbon copies, letterhead, computer signed) need the `print` ability.
- The reference number, the sequence number, the date, and the extra fields need the `update` ability.
- The signed scan upload needs `print`, not `update`. Scans come back after the source is locked.
- The preview route `letters.preview` needs `print`. If the letter cannot be printed, the preview shows the messages with the status 422. It does not redirect.

Letterheads use `LetterheadPolicy` and the `internal.controls.letterheads.*` permissions.

## Manual Letters

A system administrator makes a manual letter on the `system.letters.compose` route. It needs the `system.letters.compose` permission. The letter has no letter row and takes no number from a sequence.

The administrator chooses a letter type and a start point:

- A blank letter.
- A letter source. The page loads the letter data from the source.
- An existing letter of the source. The page also loads its saved values.

The administrator can change any value. For a start from a source, the administrator must have the `view` ability on the source. `PrintManualLetter` checks that the source belongs to the letter type, and that the letter belongs to the source and the type.

Each print writes an audit entry with the event `printed-manual-letter`. The entry holds the letter type, the letter data, the extra data, the print details (reference number, date, signer, carbon copies), the source, and the letter. Image values become media on the audit entry. A preview writes nothing.

`PruneAuditLog` archives old entries, then deletes their media and rows. See `AUDIT_RETENTION_DAYS` in `config/system.php`.

The signer list on this page holds all staff with a position, in any organization.

## Server Setup

Each server needs Node, Puppeteer, and Chrome 131 or later. Chrome 131 supports the CSS page margin boxes that the letters use.

- `composer setup` runs `bunx puppeteer browsers install chrome-headless-shell`. Run it on each server.
- Set the package variables in `.env` when the defaults do not fit: `LARAVEL_PDF_NODE_BINARY`, `LARAVEL_PDF_NPM_BINARY`, `LARAVEL_PDF_CHROME_PATH`, `LARAVEL_PDF_NODE_MODULES_PATH`, `LARAVEL_PDF_BIN_PATH`, and `LARAVEL_PDF_TEMP_PATH`.
- Set `LARAVEL_PDF_NO_SANDBOX` to true when Chrome runs as root or in a container.
- Run `bun run build` before the first render. The letter page reads the built `resources/css/letter.css`.
- Keep `app.timezone` at `UTC`.
- CI needs `poppler-utils` for the tests that read PDF text. `.github/workflows/tests.yml` installs it.

`RenderPdf` blocks every `http://` and `https://` request and turns off JavaScript. The page has a content security policy that allows only inline styles and data images. A letter cannot load a file or a URL.

A render takes one to two seconds and runs inside the request. A large batch must run in a queue that you write.

## Limits

- No `.docx` output.
- No email sending.
- No appended PDFs.
- No QR code. Apps add their own.
- No page-1-only HTML footer at the bottom of the page. Use an image.
- A render runs inside the request.

## Rules

The names of the tests of the module. A failing test fails the build, so each rule is true.

### Domain

<!-- generated:test-rules path="tests/Feature/Domain/Shared/Letter" -->

### AssignReferenceNumber

- assigning twice gives one number
- two letters of one sequence get 1 and 2
- two organizations get separate sequences
- the key uses the signature profile, not the newest profile
- without a signer the key uses the printer organization
- a printer without an organization and no signer is refused
- the key does not change when the signer changes after the number
- an empty date becomes today
- a kept date stays
- manual numbering without a reference number is refused
- manual numbering keeps a typed reference number
- a yearly sequence starts again at 1 in the next year
- a stale second instance gets the first instance number
- a backdated letter takes the year of the day its number is given
- a number that repeats the reference of a deleted letter is refused and not used
- the key uses the organization of a deleted signature profile
- a letter that already has a reference number but no date gets today

### DeleteLetter

- deleting a letter soft deletes it
- a delete in the current cycle records an undo
- a deleted number is not given again
- a delete after the cycle ended records nothing and keeps the number
- a delete of a typed reference number records nothing

### FindLetterhead

- the letterhead for the branch and department wins
- the branch letterhead wins over the department letterhead, and the department letterhead over the organization letterhead
- two departments with the same name in two branches get their own letterheads
- a placement without a branch or department matches only organization-wide letterheads
- no match gives null

### PrintLetter

- a preview assigns no number and keeps nothing
- a preview of a dated letter uses its date
- a preview of an undated letter uses today
- a print assigns a number and does not mark a draft
- a print keeps the number of a reprint
- extra fields reach the view
- the date prints in the format and locale of the type
- the signer name and position come from the signature profile, also after the position is deleted
- the carbon copies print
- a letter with a letterhead prints its images
- a letter without a letterhead prints with none when none is found
- a letter without the letterhead option prints with none
- a deleted image in the letter data refuses the print
- a refused print assigns no number and no date and leaves the sequence
- a deleted image in the mapped data refuses the print and assigns no number
- the stored date is the date the data was built with
- a print stores the date it rendered
- a letter type mapping error is not reported as a deleted file
- a printer without a profile prints a letter with the letterhead option
- a kept print stores the bytes with the printer and time
- a type without kept prints stores nothing
- a preview of a type with kept prints stores nothing
- the file downloads and shows inline as a pdf
- a real print keeps the downloaded bytes
- a real print numbers the items

### PrintManualLetter

- a print writes one audit entry with the values
- a print makes no letter row and does not move the sequence
- a preview writes no audit entry and marks the page as a draft
- a ticked group prints its section
- an unticked group prints no section
- an upload ends on the audit entry and its uuid is logged
- a preview keeps the upload out of the audit log
- an image of the source stays on the source and its uuid is logged
- an image uuid of another owner is refused
- a missing required value is refused with a key for the part
- a signer without a position is refused
- a letter start logs the letter id
- a letter of another source or type is refused

### RenderPdf

- it renders a view to a pdf
- it marks a draft
- it formats dates in the letter locale and time zone
- it prints html in letter data as text
- it loads no local file

### ResolveLetterhead

- the letterhead of the letter type wins
- the signer placement wins over the printer placement
- the printer placement is used without a signer
- the printer placement is used when the signer placement has no letterhead
- no match gives null

### SaveLetter

- saving keeps the source, the type, the reference number, the date, and the carbon copies
- a signer makes one sign action
- a new signer replaces the actor of the one sign action
- no signer deletes the sign action
- a signer without a position is refused
- the unchanged signer who lost the position passes
- changing to a signer without a position is still refused
- a letter type that requires a signer refuses no signer
- a letter type that hides the signer refuses a signer
- computer signed forces the letterhead
- a letter type that is not allowed for the user is refused
- bad extra values are refused and nothing is saved
- extra values are kept
- an extra image is stored in the data collection and its uuid is kept
- an extra image that is kept by uuid is not stored twice
- a refused save leaves no stored image and keeps the upload for a retry
- a typed reference number keeps the sequence key of the printer organization
- a typed duplicate reference number is refused
- a typed reference number of a deleted letter is refused
- the same typed reference number in another sequence passes
- a letter keeps its own typed reference number when saved again
- the sequence key does not change after the letter has a reference number
- a unique index error becomes a validation error
- automatic numbering ignores a typed reference number
- an override number is formatted by the letter type
- an override number above the counter is skipped by the counter
- an override number at or below the counter is accepted when no letter has it
- an override number that a letter has is refused
- a refused new letter stays unsaved
- an override number below 1 is refused
- clearing a typed reference number clears the sequence key

### SaveLetterhead

- saving keeps the placement, the name, and one header and one footer image
- a new header replaces the old one
- a header that is 2479 px wide is refused
- a second letterhead for the same placement gives a validation error
- a placement without a branch or department is unique too
- another placement, or a placement freed by a delete, is allowed
- saving a letterhead again keeps its own placement

### UploadSignedScan

- a new signed scan replaces the old one

### Letter

- carbon copies round-trip through the database
- a new letter has no carbon copies and no extra values
- the letter type comes from the registry
- extra values round-trip through the database
- the signature is the sign action
- a soft-deleted letter keeps its row and its source
- a reference number is unique within its sequence key while the letter is not deleted
- two letters without a sequence key cannot share a reference number
- a carbon copy that is not a carbon copy is refused
- a carbon copy array is stored as a carbon copy
- the factory makes a letter of the registered type
- the factory asks for a registered letter type

### LetterheadObserver

- a letterhead can have a branch and a department of its organization
- a letterhead cannot have a branch of another organization
- a letterhead cannot have a department of another organization
- a letterhead cannot move to a branch of another organization

### LetterPolicy

- each letter ability follows the source policy
- create reads the source from the unsaved letter
- print passes when the source allows view but not update
- the policy checks the given user, not the logged-in user
- a letter without a source passes no ability
- a letter whose source is gone passes no ability
- create without a type passes when the user may create one letter type of the source
- create without a type fails when the source has no letter type
- a letter type that is not allowed for the user passes only view and delete
- create without a type skips letter types that are not allowed for the user

### LetterheadPolicy

- each ability needs its own letterhead permission
- another letterhead permission grants nothing
- viewing the audit log needs its own permission

### Field

- it declares a field with a name, a type and children
- it chains optional and label without changing the original field
- it gives group, list and table rules with dot keys
- it refuses an image column in a table
- it gives table columns a rule under each row
- it requires the fields of a group only when the group is filled
- it lets an optional field stay empty
- it accepts only the declared options of a choice
- it accepts an image uuid of an owner
- it refuses an image uuid of another model
- it refuses a path as an image
- it accepts a temporary upload as an image
- it treats a group with only empty children as absent
- it still requires the children of a filled group
- it refuses a temporary upload that is not an image
- it refuses a malformed uuid as an image
- it refuses an svg as an image
- it refuses an invalid table row
- it refuses a wrong number, date or yes-no value

### LetterData

- it reads declared values by name
- it returns null for an empty optional field
- it keeps line breaks in lines
- it reads an empty group as null and a filled group as letter data
- it reads a table as a list of letter data
- it throws for an undeclared name
- it turns an image uuid into a data uri and keeps the uuid in the array
- it turns a temporary upload into a data uri
- it turns the data into an array that json can store
- it refuses the image uuid of another model
- it refuses an image uuid when there is no owner
- it refuses an svg image
- it refuses a temporary upload that is not a raster image
- it refuses to store the data of a temporary upload
- it stores an empty image as null
- it stores an empty table as an empty list
- it passes the media owners to the fields of a group

### LetterType

- it turns the mapping into validated letter data
- it refuses undeclared letter data
- it refuses missing letter data
- it refuses a value that fails its field rule
- it maps letter abilities to source abilities
- it has safe defaults
- the label is the headline of the config key by default
- a letter type can override its label

### LetterTypes

- it lists every configured letter type by key
- it finds a letter type by key
- it refuses an unknown key
- it gives the config key of a letter type
- it returns only the letter types of the given source

### ReferenceSequence

- the key joins its parts with a colon
- a key part that is not a string or an int is refused
- the first number registers the sequence with its reset and recorded undos
- numbers count up within one key
- a reserved number above the counter is never given
- a reserved number at or below the counter changes nothing
- a released number in the current cycle is recorded and not given again
- releasing a number twice records one undo
- a released number of an ended cycle records nothing
- a released number after the cycle ended but before the next number records nothing
- releasing a number of an unknown sequence does nothing
- a sequence with other settings is registered again with recorded undos and its reset

<!-- /generated:test-rules -->

### Letter Components

<!-- generated:test-rules path="tests/Feature/Views/Components/Letter" -->

### DataFields

- each field type renders its input bound to the prefix and the name
- a choice lists its options
- an image renders the file upload bound to the field
- a label comes from the field, or from its name
- an optional field is not marked required
- a locked field is disabled and the others are not
- a locked field of every type is disabled
- a locked image upload is disabled
- a locked group disables the fields inside it
- a group switch is bound to the groups key and shows the fields only when on
- a list renders one input and one remove button for each row, and an add button
- a table renders the columns of each row bound to the row index
- the add and remove buttons change the property in the browser
- a locked list or table has no add or remove buttons

### Page

- it refuses an html first header with an html header
- it refuses an html first footer
- it prints a first page image header and footer on page 1 only
- it prints an every page image header and footer on each page at full width
- it prints an html header and footer on each page
- it keeps body text clear of an html header and footer
- it prints page numbers as page of pages

<!-- /generated:test-rules -->

### Manual Letter Page

<!-- generated:test-rules path="tests/Feature/Pages/System/Letters" -->

### Compose

- users without system.letters.compose cannot open the page
- users with system.letters.compose can open the page
- a user without the permission cannot run an action
- the type list shows the label of each letter type
- a blank start gives empty fields
- a source start fills the mapping
- a source start without the view ability of the source is refused
- the source picker lists only sources the admin can view, found by id or name
- a letter start fills the print details and the extra values of the letter
- a print downloads the letter and writes one audit entry, no letter row, no mark
- a preview downloads a draft and writes no audit entry
- a ticked group prints its section and an unticked one does not
- a group that is on with no filled child is refused
- a missing required value shows an error on its field
- an upload ends on the audit entry
- the same upload prints again after a fix
- a signer must have a position and be in the list
- a required signer is checked
- an admin without an organization can pick a signer and print a type that needs one
- an admin can pick staff and departments of another organization
- a letter start keeps a saved signer from another organization
- a source that stops being viewable after the start cannot be printed
- a letter id of another source is not found
- an empty letter id is ignored
- the source cannot be set from the browser
- the source picker skips sources that cannot be viewed until it finds enough

<!-- /generated:test-rules -->

### Other Tests

These files test the letters table, the letter modal, the preview route, and the letterhead components:

- `tests/Feature/Livewire/DataTables/LetterDataTableTest.php`
- `tests/Feature/Livewire/Components/LetterModalTest.php`
- `tests/Feature/Http/Controllers/LetterPreviewControllerTest.php`
- `tests/Feature/Views/Components/LetterTest.php`
- `tests/Feature/Views/Components/LetterheadTest.php`
- `tests/Feature/Livewire/DataTables/Internal/Organization/LetterheadDataTableTest.php`
- `tests/Feature/Pages/Internal/Controls/Organization/LetterheadModalTest.php`
- `tests/Browser/LetterDataFieldsTest.php`
