# File Upload

`<x-file-upload>` uploads files and keeps them with a model through Media Library. The user drops files on the field or picks them, sees each file in a list, and can remove the files or change their order. The page stores the files when the user saves the form.

```blade
<x-file-upload label="Documents" wire:model="form.documents" :rules="['mimes:pdf', 'max:10240']" multiple />
```

It takes every [field prop](field.md). It puts the Livewire component `App\Livewire\Components\FileUpload` in the field, and that component wraps `flux:file-upload`. `rules` is required.

## The Property

The property holds one item for each file. The item of a stored file names its media by its uuid. The item of a new file holds a signed reference to the upload, until the page stores the file. The page does three things:

1. In `mount()`, it fills the property with `FileUpload::items()`, which gives the media of one collection of a model, in their order. The model needs the kit's media trait, see [The Model](#the-model).
2. Before it saves, it validates the property with the `Uploads` rule and the same file rules. The browser sends the items, so the field's own check is not enough. The rule also tells the user when an upload has expired: Livewire deletes uploads after a day.
3. It stores the items with the `SyncMedia` Action. New files join the collection, removed files leave it, and the collection takes the order of the items.

```php
use App\Domain\Shared\Media\Actions\SyncMedia;
use App\Domain\Shared\Media\Rules\Uploads;
use App\Livewire\Components\FileUpload;

/** @var list<array{uuid: string, name: string, size: int, mime_type: string, upload?: ?string}> */
public array $documents = [];

public function mount(): void
{
    $this->documents = FileUpload::items(auth()->user(), 'documents');
}

public function save(SyncMedia $syncMedia): void
{
    $this->validate([
        'documents' => ['required', 'max:5', new Uploads(['mimes:pdf', 'max:10240'])],
    ]);

    $syncMedia->execute(auth()->user(), $this->documents, 'documents');
}
```

`SyncMedia` keeps only the media of the model and the collection that it gets, and it adds only uploads that Livewire signed. So a changed request cannot move or delete other files.

## Rules and Limits

`rules` checks each file when it arrives. It takes an array, or a string with the rules between pipes. A file that fails stays out of the list, and the field says why. Without `multiple`, the field takes one file. With `multiple`, `max` sets the largest number of files. When the field is full, the dropzone disappears until the user removes a file.

```blade
<x-file-upload label="Photo" wire:model="form.photo" rules="image|max:2048" />
<x-file-upload label="Receipts" wire:model="form.receipts" :rules="['mimes:pdf,jpg,png', 'max:5120']" multiple max="3" />
```

The kit lets Livewire and Media Library take files up to 1 GB (`livewire.temporary_file_upload.rules` and `media-library.max_file_size`), and PHP must allow the size too. So set the limit of each field in its `rules`.

## Dropzone

The file picker offers the types of the `mimes`, `extensions`, `mimetypes`, and `image` rules. The dropzone lists the extensions of the `mimes` and `extensions` rules, and the size of the `max` rule. `accept`, `heading`, and `text` replace them. `inline` puts the dropzone on one line.

```blade
<x-file-upload label="Payslip" wire:model="form.payslip" :rules="['mimes:pdf', 'max:2048']" heading="Drop your latest payslip here" text="PDF from your employer" inline />
```

## Order

With `multiple`, the user can drag the files into a new order. `SyncMedia` stores the order, and `FileUpload::items()` gives the files in it.

## Disabled

`disabled` shows the files without the dropzone, and the user cannot remove or move them. It follows the page: when the page turns it on or off, the field follows.

```blade
<x-file-upload label="Submitted forms" wire:model="form.submitted" :rules="['mimes:pdf']" multiple disabled />
```

## Stored Files

Each stored file links to the `media.show` route. The route asks the policy of the model that owns the file for its `view` ability, so give that policy a `view` method. For a file of a user, `UserPolicy` lets the user open it, and also a user with `admin.controls.users.view` or `admin.controls.users.view-any`. An image opens in the browser, and other files download. An SVG image downloads too, because it can hold a script.

The media disk is private, so this route is the only way to a stored file. `getUrl()` of a media gives this route too, also for a conversion, such as `getUrl('preview')`. A conversion is an image, so it always opens in the browser.

## The Model

Give the model media with the kit's `HasMedia` contract and `InteractsWithMedia` trait, from `app/Domain/Shared/Common`, not with Spatie's:

- The trait makes a `preview` of each image on the media queue. A preview is a WebP image of at most 300 by 300 pixels, about 10 KB for a phone photo. A smaller image keeps its size, and transparent parts stay transparent.
- The list shows the image itself until its preview exists. An SVG image gets no preview, because ImageMagick can fetch the files that an SVG links to while it draws it.
- `getMedia()` and `hasMedia()` take the name of a collection or an enum case, and so do `FileUpload::items()` and `SyncMedia`.
- To change the conversions of a model, write its own `registerMediaConversions()`. Add the `preview` again, so that the list keeps its small images.

## Reference

### Props

| Prop          | Default | Description                                                                                                                 |
|---------------|---------|-----------------------------------------------------------------------------------------------------------------------------|
| Field props   |         | `label`, `required`, `badge`, `hint`, `description`, `description:trailing`, and `no-error-message`. See [Field](field.md). |
| `rules`       |         | Required. The validation rules for each file, as an array or as a string with the rules between pipes.                     |
| `multiple`    | `false` | Takes more than one file, and lets the user change their order.                                                             |
| `max`         |         | The largest number of files with `multiple`.                                                                                |
| `accept`      |         | The file types that the picker offers. By default, they come from the rules.                                                |
| `heading`     |         | The heading of the dropzone. By default, it asks the user to drop a file or click.                                          |
| `text`        |         | The text below the heading. By default, the types and the largest size from the rules.                                      |
| `inline`      | `false` | Puts the dropzone on one line.                                                                                              |
| `disabled`    | `false` | Shows the files without the dropzone.                                                                                       |

### Slots

| Slot        | Description                                                                    |
|-------------|--------------------------------------------------------------------------------|
| Field slots | `label`, `badge`, `hint`, `actions`, and `description`. See [Field](field.md). |

### Attributes

`wire:model` with any modifier binds the items, and the field needs it. `class` goes to the field.
