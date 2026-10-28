<?php

declare(strict_types=1);

use App\Domain\User\Models\User;
use App\Livewire\Components\FileUpload;
use Dom\HTMLDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\ViewException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Livewire;
use Tests\Fixtures\Enums\Folder;

test('a file that passes the rules becomes an item with a signed upload', function (): void {
    $component = Livewire::test(FileUpload::class, ['fileRules' => ['mimes:pdf', 'max:1024']])
        ->set('uploads', UploadedFile::fake()->create('report.pdf', 120, 'application/pdf'));

    $item = $component->get('items')[0];

    expect($component->get('items'))->toHaveCount(1)
        ->and([$item['name'], $item['size'], $item['mime_type']])->toBe(['report.pdf', 120 * 1024, 'application/pdf'])
        ->and(TemporaryUploadedFile::unserializeFromLivewireRequest($item['upload']))->toBeInstanceOf(TemporaryUploadedFile::class);
    $component->assertHasNoErrors();
});

test('a file that fails the rules is left out with its name and the reason', function (): void {
    $component = Livewire::test(FileUpload::class, ['fileRules' => ['mimes:pdf']])
        ->set('uploads', UploadedFile::fake()->image('photo.png'));

    expect($component->get('items'))->toBe([])
        ->and($component->errors()->first('uploads'))->toBe('photo.png was not added. The file field must be a file of type: pdf.');
});

test('a full field leaves out more files and says how many it takes', function (bool $multiple, ?int $max, string $message): void {
    $component = Livewire::test(FileUpload::class, ['fileRules' => ['file'], 'multiple' => $multiple, 'max' => $max])
        ->set('items', [
            ['uuid' => 'first', 'name' => 'a.pdf', 'size' => 1, 'mime_type' => 'application/pdf'],
            ['uuid' => 'second', 'name' => 'b.pdf', 'size' => 1, 'mime_type' => 'application/pdf'],
        ])
        ->set('uploads', $multiple ? [UploadedFile::fake()->create('c.pdf')] : UploadedFile::fake()->create('c.pdf'));

    expect(array_column($component->get('items'), 'uuid'))->toBe(['first', 'second'])
        ->and($component->errors()->first('uploads'))->toBe($message);
})->with([
    'one file' => [false, null, 'c.pdf was not added. You can add 1 file.'],
    'a maximum' => [true, 2, 'c.pdf was not added. You can add up to 2 files.'],
]);

test('remove takes out the item and deletes its upload', function (): void {
    $component = Livewire::test(FileUpload::class, ['fileRules' => ['file'], 'multiple' => true])
        ->set('uploads', [UploadedFile::fake()->create('a.pdf'), UploadedFile::fake()->create('b.pdf')]);
    [$first, $second] = $component->get('items');
    $file = TemporaryUploadedFile::unserializeFromLivewireRequest($first['upload']);

    $component->call('remove', $first['uuid']);

    expect(array_column($component->get('items'), 'name'))->toBe(['b.pdf'])
        ->and($file->exists())->toBeFalse()
        ->and(TemporaryUploadedFile::unserializeFromLivewireRequest($second['upload'])->exists())->toBeTrue();
});

test('sort moves an item to its new position', function (): void {
    $component = Livewire::test(FileUpload::class, ['fileRules' => ['file'], 'multiple' => true])
        ->set('items', array_map(fn (string $uuid): array => ['uuid' => $uuid, 'name' => "{$uuid}.pdf", 'size' => 1, 'mime_type' => 'application/pdf'], ['a', 'b', 'c']));

    $component->call('sort', 'c', 0);

    expect(array_column($component->get('items'), 'uuid'))->toBe(['c', 'a', 'b']);
});

test('a disabled field takes no uploads and keeps its items in place', function (): void {
    $items = [
        ['uuid' => 'a', 'name' => 'a.pdf', 'size' => 1, 'mime_type' => 'application/pdf'],
        ['uuid' => 'b', 'name' => 'b.pdf', 'size' => 1, 'mime_type' => 'application/pdf'],
    ];
    $component = Livewire::test(FileUpload::class, ['fileRules' => ['file'], 'multiple' => true, 'disabled' => true])->set('items', $items);

    $component->set('uploads', [UploadedFile::fake()->create('c.pdf')])
        ->call('remove', 'a')
        ->call('sort', 'b', 0);

    expect($component->get('items'))->toBe($items);
    $component->assertDontSeeHtml('data-flux-file-upload-dropzone')
        ->assertDontSeeHtml('data-flux-file-item-remove');
});

test('a file upload without rules is refused', function (): void {
    expect(fn () => Livewire::test(FileUpload::class))->toThrow(ViewException::class, 'A file upload needs rules');
});

test('the dropzone accepts the file types of the rules and shows their limits', function (array $rules, ?string $accept, string $text): void {
    $document = HTMLDocument::createFromString(Livewire::test(FileUpload::class, ['fileRules' => $rules])->html(), LIBXML_NOERROR);

    expect($document->querySelector('ui-file-upload')?->getAttribute('accept'))->toBe($accept)
        ->and(preg_replace('/\s+/', ' ', trim((string) $document->querySelector('[data-flux-file-upload-dropzone]')?->textContent)))->toBe($text);
})->with([
    'types and size' => [['mimes:pdf,jpg', 'max:10240'], '.pdf,.jpg', 'Drop a file here or click to browse PDF, JPG up to 10 MB'],
    'extensions' => [['extensions:csv'], '.csv', 'Drop a file here or click to browse CSV'],
    'size only' => [['file', 'max:512'], null, 'Drop a file here or click to browse Up to 512 KB'],
    'images' => [['image'], 'image/*', 'Drop a file here or click to browse'],
    'mime types' => [['mimetypes:application/pdf'], 'application/pdf', 'Drop a file here or click to browse'],
]);

test('the heading, text, and accepted types can be set', function (): void {
    $document = HTMLDocument::createFromString(Livewire::test(FileUpload::class, ['fileRules' => ['mimes:pdf'], 'heading' => 'Add a payslip', 'text' => 'Your latest one', 'accept' => 'application/pdf'])->html(), LIBXML_NOERROR);

    expect($document->querySelector('ui-file-upload')?->getAttribute('accept'))->toBe('application/pdf')
        ->and($document->querySelector('[data-flux-file-upload-dropzone]')?->textContent)->toContain('Add a payslip')->toContain('Your latest one');
});

test('a stored image shows its preview or itself and opens in a new tab, and another stored file downloads, all through the media route', function (): void {
    $document = HTMLDocument::createFromString(Livewire::test(FileUpload::class, ['fileRules' => ['file'], 'multiple' => true])
        ->set('items', [
            ['uuid' => 'photo', 'name' => 'photo.png', 'size' => 1, 'mime_type' => 'image/png'],
            ['uuid' => 'scan', 'name' => 'scan.jpg', 'size' => 1, 'mime_type' => 'image/jpeg', 'preview' => true],
            ['uuid' => 'report', 'name' => 'report.pdf', 'size' => 1, 'mime_type' => 'application/pdf'],
        ])
        ->html(), LIBXML_NOERROR);

    $photo = $document->querySelector('[wire\:key="photo"]');
    $report = $document->querySelector('[wire\:key="report"]');

    expect($photo?->querySelector('img')?->getAttribute('src'))->toBe(route('media.show', 'photo'))
        ->and($document->querySelector('[wire\:key="scan"] img')?->getAttribute('src'))->toBe(route('media.show', ['scan', 'preview']))
        ->and($photo?->querySelector('a[aria-label="Open photo.png"]')?->getAttribute('target'))->toBe('_blank')
        ->and($report?->querySelector('img'))->toBeNull()
        ->and($report?->querySelector('a[aria-label="Download report.pdf"]')?->getAttribute('href'))->toBe(route('media.show', 'report'));
});

test('items lists the media of one collection in their order', function (): void {
    Storage::fake('media');
    $user = User::factory()->create();
    $first = $user->addMedia(UploadedFile::fake()->createWithContent('first.pdf', '%PDF-1.4 first'))->toMediaCollection('documents');
    $second = $user->addMedia(UploadedFile::fake()->createWithContent('second.txt', 'second file'))->toMediaCollection('documents');
    $user->addMedia(UploadedFile::fake()->createWithContent('avatar.txt', 'avatar'))->toMediaCollection('avatar');
    $second->update(['order_column' => 0]);

    $items = FileUpload::items($user->fresh(), Folder::Documents);

    expect($items)->toBe([
        ['uuid' => $second->uuid, 'name' => 'second.txt', 'size' => 11, 'mime_type' => 'text/plain', 'preview' => false],
        ['uuid' => $first->uuid, 'name' => 'first.pdf', 'size' => 14, 'mime_type' => 'application/pdf', 'preview' => false],
    ]);
});

test('an unset bound property counts as no items', function (): void {
    $component = Livewire::test(FileUpload::class, ['fileRules' => ['file']])->set('items');

    expect($component->get('items'))->toBe([]);
    $component->assertSeeHtml('data-flux-file-upload-dropzone');
});
