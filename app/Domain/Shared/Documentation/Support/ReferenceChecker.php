<?php

declare(strict_types=1);

namespace App\Domain\Shared\Documentation\Support;

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use Composer\Autoload\ClassLoader;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class ReferenceChecker
{
    private const array EXTENSIONS = ['php', 'md', 'json', 'js', 'ts', 'css', 'neon', 'xml', 'yml', 'yaml', 'stub', 'sh'];

    /**
     * @var Collection<int, string>
     */
    private readonly Collection $permissions;

    /**
     * @var Collection<int, string>
     */
    private readonly Collection $routes;

    /**
     * @var array<string, list<string>>
     */
    private array $classesByName = [];

    /**
     * @var list<string>
     */
    private array $namespaces = [];

    /**
     * @var Collection<int, string>
     */
    private readonly Collection $files;

    /**
     * @var Collection<int, string>
     */
    private readonly Collection $paths;

    /**
     * @var Collection<int, string>
     */
    private readonly Collection $variables;

    /**
     * @var array<int, string>
     */
    private readonly array $columns;

    public function __construct()
    {
        $this->permissions = new PermissionSeeder()->data();
        $this->routes = collect(resolve(Router::class)->getRoutes()->getRoutesByName())->keys();
        $this->indexClasses();

        $files = collect(iterator_to_array(Finder::create()->files()->in(base_path())->exclude(['vendor', 'node_modules', 'storage', 'public', 'bootstrap/cache'])));
        $this->files = $files->map(fn (SplFileInfo $file): string => $file->getFilename())->unique()->values();

        $this->paths = $files->map(fn (SplFileInfo $file): string => $file->getRelativePathname())->values();

        $example = base_path('.env.example');

        $this->variables = collect(iterator_to_array(Finder::create()->files()->in(config_path())->name('*.php')))
            ->flatMap(fn (SplFileInfo $file): array => $this->matches("/env\('([A-Z0-9_]+)'/", $file->getContents()))
            ->merge($this->matches('/^([A-Z0-9_]+)=/m', is_file($example) ? (string) file_get_contents($example) : ''))
            ->unique()
            ->values();

        $this->columns = collect(Schema::getTableListing(schemaQualified: false))
            ->flatMap(fn (string $table): array => array_map(fn (string $column): string => "{$table}.{$column}", Schema::getColumnListing($table)))
            ->values()
            ->all();
    }

    public function problem(string $reference): ?string
    {
        $reference = str($reference)->chopStart('can:')->value();

        if (preg_match('/\s|^[\/$]|\.$|^[\d.]+$|:\/\//', $reference) === 1) {
            return null;
        }

        if (str_contains($reference, '/')) {
            return $this->pathProblem((string) preg_replace('/:\d+(?:-\d+)?$/', '', $reference));
        }

        if (preg_match('/^\\\\?([A-Z]\w*(?:\\\\[A-Z]\w*)*)::(\w+)(?:\(\))?$/', $reference, $member) === 1) {
            return $member[2] === 'class'
                ? ($this->classes($member[1]) === [] ? 'is not a class' : null)
                : $this->memberProblem($member[1], $member[2]);
        }

        if (preg_match('/^\\\\?[A-Z][a-z]\w*(?:\\\\[A-Z]\w*)*$/', $reference) === 1) {
            return $this->classes($reference) === [] ? 'is not a class' : null;
        }

        if (preg_match('/^[A-Z][A-Z0-9]*(?:_[A-Z0-9]+)+$/', $reference) === 1) {
            return $this->variables->contains($reference) ? null : 'is not read by a config file or listed in .env.example';
        }

        if (preg_match('/\.(?:'.implode('|', self::EXTENSIONS).')$/', $reference) === 1) {
            return $this->files->contains($reference) ? null : 'is not a file';
        }

        if (preg_match('/^[a-z0-9_*-]+(?:\.[a-z0-9_*-]+)+$/', $reference) === 1) {
            return $this->isName($reference) ? null : 'is not a permission, route name, config key, or table column';
        }

        return null;
    }

    private function pathProblem(string $path): ?string
    {
        if (str_contains($path, '*')) {
            return $this->matchesFiles($path) ? null : 'matches no file';
        }

        return file_exists(base_path($path)) || trans()->has($path) ? null : 'is not a file, folder, or translation key';
    }

    private function matchesFiles(string $pattern): bool
    {
        if (! str_contains($pattern, '**')) {
            return (glob(base_path($pattern)) ?: []) !== [];
        }

        $regex = '#^'.strtr(preg_quote($pattern, '#'), ['\*\*/' => '(?:.*/)?', '\*\*' => '.*', '\*' => '[^/]*', '\?' => '[^/]']).'$#';

        return $this->paths->contains(fn (string $path): bool => preg_match($regex, $path) === 1);
    }

    private function indexClasses(): void
    {
        $root = realpath(base_path()) ?: base_path();

        foreach (ClassLoader::getRegisteredLoaders() as $loader) {
            foreach ($loader->getPrefixesPsr4() as $namespace => $directories) {
                foreach ($directories as $directory) {
                    $directory = realpath($directory);

                    if ($directory === false || ! str_starts_with($directory, $root) || str_starts_with($directory, $root.DIRECTORY_SEPARATOR.'vendor')) {
                        continue;
                    }

                    $this->namespaces[] = $namespace;

                    foreach (Finder::create()->files()->in($directory)->name('*.php') as $file) {
                        $this->classesByName[$file->getBasename('.php')][] = $namespace.str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());
                    }
                }
            }
        }
    }

    /**
     * @return list<string>
     */
    private function classes(string $name): array
    {
        $name = ltrim($name, '\\');

        return $this->classesByName[$name] ?? (class_exists($name) || interface_exists($name) || trait_exists($name) || enum_exists($name) ? [$name] : []);
    }

    private function memberProblem(string $class, string $member): ?string
    {
        $classes = $this->classes($class);

        if ($classes === []) {
            return 'names a class that does not exist';
        }

        $own = array_filter($classes, fn (string $name): bool => str($name)->startsWith($this->namespaces));

        return $own === [] || collect($own)->contains(fn (string $name): bool => method_exists($name, $member) || property_exists($name, $member) || defined("{$name}::{$member}"))
            ? null
            : "names a member that {$class} does not have";
    }

    private function isName(string $name): bool
    {
        return $this->permissions->contains(fn (string $permission): bool => str($permission)->is($name))
            || $this->routes->contains(fn (string $route): bool => str($route)->is($name))
            || config()->has($name)
            || in_array($name, $this->columns, true);
    }

    /**
     * @return list<string>
     */
    private function matches(string $pattern, string $subject): array
    {
        preg_match_all($pattern, $subject, $matches);

        return $matches[1];
    }
}
