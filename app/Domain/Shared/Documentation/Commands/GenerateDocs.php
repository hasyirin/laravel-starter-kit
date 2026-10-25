<?php

declare(strict_types=1);

namespace App\Domain\Shared\Documentation\Commands;

use App\Domain\Shared\Documentation\Contracts\Section;
use App\Domain\Shared\Documentation\Support\Document;
use App\Domain\Shared\Documentation\Support\ReferenceChecker;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\Finder\Finder;

#[Description('Fill the generated sections of the docs from the code, and check the code names in the docs')]
#[Signature('docs:generate
        {--check : Write nothing, and fail when a generated section is out of date or a doc names code that does not exist}
        {--path=docs : The folder with the docs, relative to the project root}')]
class GenerateDocs extends Command
{
    public function handle(): int
    {
        $connection = config()->string('database.default');
        $store = config()->string('cache.default');

        try {
            $this->useDocsDatabase();

            return $this->generate();
        } finally {
            config(['database.default' => $connection, 'cache.default' => $store]);
            resolve(PermissionRegistrar::class)->initializeCache();
            DB::purge('docs');
        }
    }

    private function useDocsDatabase(): void
    {
        config([
            'database.connections.docs' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true],
            'database.default' => 'docs',
            'cache.default' => 'array',
        ]);

        resolve(PermissionRegistrar::class)->initializeCache();

        $this->callSilently('migrate', ['--force' => true]);
        $this->callSilently('db:seed', ['--force' => true]);
    }

    private function generate(): int
    {
        $folder = (string) $this->option('path');
        $checker = new ReferenceChecker;
        $stale = [];
        $broken = [];

        foreach (Finder::create()->files()->in(str_starts_with($folder, '/') ? $folder : base_path($folder))->name('*.md')->sortByName() as $file) {
            $path = "{$folder}/{$file->getRelativePathname()}";
            $contents = $file->getContents();
            $generated = new Document($contents)->render(fn (string $name, array $options): string => $this->section($name)->render($options));

            if ($generated !== $contents && $this->option('check')) {
                $stale[] = "{$path} is out of date. Run php artisan docs:generate";
            } elseif ($generated !== $contents) {
                file_put_contents($file->getPathname(), $generated);
                $this->components->info("Updated {$path}.");
            }

            foreach (new Document($this->option('check') ? $contents : $generated)->references() as ['line' => $line, 'reference' => $reference]) {
                $problem = $checker->problem($reference);

                if ($problem !== null) {
                    $broken[] = "{$path}:{$line} `{$reference}` {$problem}";
                }
            }
        }

        if ($stale !== [] || $broken !== []) {
            $this->components->error('The docs do not match the code.');
            $this->components->bulletList([...$stale, ...$broken]);

            if ($broken !== []) {
                $this->components->info('Fix each name in backticks, or remove the backticks if it is not a code name.');
            }

            return self::FAILURE;
        }

        $this->components->info('The docs match the code.');

        return self::SUCCESS;
    }

    private function section(string $name): Section
    {
        $class = 'App\\Domain\\Shared\\Documentation\\Sections\\'.str($name)->studly();
        $section = class_exists($class) ? resolve($class) : null;

        if ($section instanceof Section) {
            return $section;
        }

        throw new InvalidArgumentException("There is no docs section named [{$name}].");
    }
}
