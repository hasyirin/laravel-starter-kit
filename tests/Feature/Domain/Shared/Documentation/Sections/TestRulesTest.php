<?php

declare(strict_types=1);

use App\Domain\Shared\Documentation\Sections\TestRules;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->tests = sys_get_temp_dir().'/tests-'.Str::random(8);
    File::ensureDirectoryExists("{$this->tests}/Models");
});

afterEach(function (): void {
    File::deleteDirectory($this->tests);
});

test('the rules are the names of the tests that run', function (): void {
    File::put("{$this->tests}/Models/RoleTest.php", <<<'PHP'
        <?php

        test('a system role cannot be deleted', function (): void {
            $this->test('not a rule');
        });

        test('a role keeps its name', fn () => true)->with(['one', 'two']);

        it('records changes', function (): void {});

        test('a planned rule', function (): void {})->todo();

        test('a skipped rule', function (): void {})->skip();

        test('it\'s escaped', function (): void {});
        PHP);
    File::put("{$this->tests}/EmptyTest.php", "<?php\n");

    expect(new TestRules()->render(['path' => $this->tests]))->toBe(<<<'MD'
        ### Role

        - a system role cannot be deleted
        - a role keeps its name
        - it records changes
        - it's escaped
        MD);
});

test('the rules need an existing test folder', function (array $options, string $message): void {
    expect(fn (): string => new TestRules()->render($options))->toThrow(InvalidArgumentException::class, $message);
})->with([
    'without a path' => [[], 'The [test-rules] section needs a path option.'],
    'with a missing folder' => [['path' => 'tests/Missing'], 'The test folder [tests/Missing] does not exist.'],
]);
