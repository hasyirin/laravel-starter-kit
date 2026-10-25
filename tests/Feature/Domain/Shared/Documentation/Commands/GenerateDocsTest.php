<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->docs = sys_get_temp_dir().'/docs-'.Str::random(8);
    File::ensureDirectoryExists($this->docs);
});

afterEach(function (): void {
    File::deleteDirectory($this->docs);
});

test('it fills the generated sections from the code, and the check passes after that', function (): void {
    File::put("{$this->docs}/permissions.md", "# Permissions\n\n<!-- generated:permission-matrix -->\n<!-- /generated:permission-matrix -->\n");

    $this->artisan('docs:generate', ['--path' => $this->docs])->assertSuccessful();
    $this->artisan('docs:generate', ['--path' => $this->docs, '--check' => true])->assertSuccessful();

    expect(File::get("{$this->docs}/permissions.md"))
        ->toStartWith("# Permissions\n\n<!-- generated:permission-matrix -->\n\n| Permission ")
        ->toContain('| `internal.access` ', 'Super Administrator')
        ->and(config('database.default'))->toBe('sqlite');
});

test('the check fails without writing when a generated section is out of date', function (): void {
    $stale = "<!-- generated:permission-matrix -->\nold\n<!-- /generated:permission-matrix -->\n";
    File::put("{$this->docs}/permissions.md", $stale);

    $this->artisan('docs:generate', ['--path' => $this->docs, '--check' => true])
        ->expectsOutputToContain('permissions.md is out of date. Run php artisan docs:generate')
        ->assertFailed();

    expect(File::get("{$this->docs}/permissions.md"))->toBe($stale);
});

test('it reports code names that do not exist, outside code blocks', function (): void {
    File::put("{$this->docs}/guide.md", implode("\n", [
        'Read `routes/web.php` and `routes/missing.php`.',
        '',
        '```php',
        '// `routes/ignored.php`',
        '```',
    ]));

    $this->artisan('docs:generate', ['--path' => $this->docs])
        ->expectsOutputToContain('guide.md:1 `routes/missing.php` is not a file or folder')
        ->doesntExpectOutputToContain('routes/ignored.php')
        ->assertFailed();
});

test('the check reports the line of a code name in the file as it is', function (): void {
    File::put("{$this->docs}/guide.md", "<!-- generated:permission-matrix -->\n<!-- /generated:permission-matrix -->\nRead `routes/missing.php`.\n");

    $this->artisan('docs:generate', ['--path' => $this->docs, '--check' => true])
        ->expectsOutputToContain('guide.md:3 `routes/missing.php` is not a file or folder')
        ->assertFailed();
});

test('it fails on a broken generated section', function (string $contents, string $message): void {
    File::put("{$this->docs}/guide.md", $contents);

    expect(fn () => $this->artisan('docs:generate', ['--path' => $this->docs])->run())->toThrow(InvalidArgumentException::class, $message);
})->with([
    'an unknown section' => ["<!-- generated:unknown -->\n<!-- /generated:unknown -->\n", 'There is no docs section named [unknown].'],
    'a missing closing marker' => ["<!-- generated:permission-matrix -->\n", 'A generated section has no matching closing marker.'],
]);

test('it leaves markers in code blocks alone', function (string $contents): void {
    File::put("{$this->docs}/guide.md", $contents);

    $this->artisan('docs:generate', ['--path' => $this->docs])->assertSuccessful();

    expect(File::get("{$this->docs}/guide.md"))->toBe($contents);
})->with([
    'a section' => ["```markdown\n<!-- generated:unknown -->\n<!-- /generated:unknown -->\n```\n"],
    'an opening marker' => ["```markdown\n<!-- generated:permission-matrix -->\n```\n"],
]);
