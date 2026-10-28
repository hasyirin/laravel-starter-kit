# Editor

`<x-editor>` is a rich text editor with a toolbar for headings, bold text, lists, links, and more. It wraps `flux:editor`, and the property gets HTML, such as `<p>Hello <strong>world</strong></p>`.

```blade
<x-editor label="Notes" wire:model="form.notes" />
```

It takes every [field prop](field.md). The kit links the label and the help text to the text area of the editor, so screen readers read them. Flux links them to the outer element only.

## Show the HTML Safely

The browser sends the HTML, so a user can save any HTML, also a script or an `onclick` attribute. Printed with `{!! !!}` as it is, that code runs for every user who opens the page. So never print it as it is. Print it through the sanitizer:

```html
<div>{!! str($article->body)->sanitizeHtml() !!}</div>
```

`sanitizeHtml()` comes with Filament and uses Symfony's HtmlSanitizer. It keeps the headings, lists, links, highlights, and alignment of the editor, and it removes scripts, event attributes such as `onclick`, and `javascript:` links. `{{ }}` is safe too, but it shows the tags as text.

## Toolbar

`toolbar` sets the buttons and their order. `|` adds a separator and `~` a spacer. The items are `heading`, `bold`, `italic`, `strike`, `underline`, `bullet`, `ordered`, `blockquote`, `subscript`, `superscript`, `highlight`, `link`, `code`, `align`, `undo`, and `redo`.

```blade
<x-editor label="Summary" wire:model="form.summary" toolbar="bold italic underline | bullet ordered | link ~ undo redo" />
```

## Placeholder and Height

`placeholder` shows text while the editor is empty. The text area is 200 to 500 pixels high. To change that, set the height of its content slot with `input-class`.

```blade
<x-editor label="Reply" wire:model="form.reply" placeholder="Write a reply" input-class="**:data-[slot=content]:min-h-24!" />
```

## Your Own Toolbar

The slot replaces the toolbar and the text area. Compose them from `flux:editor.toolbar`, its items, and `flux:editor.content`.

```blade
<x-editor label="Message" wire:model="form.message">
    <flux:editor.toolbar items="bold italic link" />

    <flux:editor.content />
</x-editor>
```

## Reference

### Props

| Prop          | Default | Description                                                                                                                 |
|---------------|---------|-----------------------------------------------------------------------------------------------------------------------------|
| Field props   |         | `label`, `required`, `badge`, `hint`, `description`, `description:trailing`, and `no-error-message`. See [Field](field.md). |
| `input-class` |         | Classes for the editor.                                                                                                     |

### Slots

| Slot        | Description                                                                    |
|-------------|--------------------------------------------------------------------------------|
| Field slots | `label`, `badge`, `hint`, `actions`, and `description`. See [Field](field.md). |
| `default`   | Your own toolbar and text area, in place of the default ones.                  |

### Attributes

All other attributes go to `flux:editor`. These include `wire:model` with any modifier, `name`, `value`, `toolbar`, `placeholder`, `variant`, `disabled`, and `invalid`. `class` goes to the field.
