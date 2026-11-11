<?php

declare(strict_types=1);

namespace App\Domain\Shared\Documentation\Sections;

use App\Domain\Shared\Documentation\Contracts\Section;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Erd implements Section
{
    private const array FRAMEWORK_TABLES = [
        'cache',
        'cache_locks',
        'failed_jobs',
        'job_batches',
        'jobs',
        'migrations',
        'password_reset_tokens',
        'sessions',
    ];

    private const array TYPES = [
        'blob' => 'binary',
        'double' => 'float',
        'jsonb' => 'json',
        'numeric' => 'decimal',
        'tinyint' => 'boolean',
        'varchar' => 'string',
    ];

    public function render(array $options): string
    {
        $tables = collect(Schema::getTables())
            ->map(fn (array $table): string => $table['name'])
            ->reject(fn (string $table): bool => in_array($table, self::FRAMEWORK_TABLES, true))
            ->sort()
            ->values();

        $lines = $tables
            ->flatMap($this->relations(...))
            ->sortBy(fn (array $relation): string => "{$relation['parent']} {$relation['child']}")
            ->map(fn (array $relation): string => $relation['line']);

        $diagram = collect(['```mermaid', 'erDiagram'])
            ->merge($tables->flatMap($this->entity(...)))
            ->merge($lines)
            ->push('```')
            ->implode("\n");

        return collect([$diagram, $this->polymorphicNote($tables)])
            ->filter()
            ->implode("\n\n");
    }

    /**
     * @return list<string>
     */
    private function entity(string $table): array
    {
        $primary = collect(Schema::getIndexes($table))
            ->filter(fn (array $index): bool => $index['primary'])
            ->flatMap(fn (array $index): array => $index['columns']);

        $foreign = collect(Schema::getForeignKeys($table))
            ->flatMap(fn (array $key): array => $key['columns']);

        $attributes = collect(Schema::getColumns($table))
            ->map(function (array $column) use ($primary, $foreign): string {
                $keys = collect(['PK' => $primary, 'FK' => $foreign])
                    ->filter(fn (Collection $columns): bool => $columns->contains($column['name']))
                    ->keys()
                    ->implode(', ');

                $parts = collect([$this->type($column['type_name']), $column['name'], $keys])->filter();

                return '        '.$parts->implode(' ');
            });

        return ["    {$table} {", ...$attributes, '    }'];
    }

    /**
     * @return array<int, array{parent: string, child: string, line: string}>
     */
    private function relations(string $table): array
    {
        $nullable = collect(Schema::getColumns($table))
            ->filter(fn (array $column): bool => $column['nullable'])
            ->map(fn (array $column): string => $column['name']);

        $partialOrExpression = collect(DB::select('select name from pragma_index_list(?) as list where list.partial = 1 or exists (select 1 from pragma_index_xinfo(list.name) where cid = -2)', [$table]))
            ->pluck('name');

        $unique = collect(Schema::getIndexes($table))
            ->filter(fn (array $index): bool => $index['unique'] && $partialOrExpression->doesntContain($index['name']))
            ->map(fn (array $index): array => $index['columns']);

        return collect(Schema::getForeignKeys($table))
            ->map(function (array $key) use ($table, $nullable, $unique): array {
                $parent = $nullable->intersect($key['columns'])->isEmpty() ? '||' : '|o';
                $children = $unique->contains($key['columns']) ? 'o|' : 'o{';
                $label = implode(', ', $key['columns']);

                return [
                    'parent' => $key['foreign_table'],
                    'child' => $table,
                    'line' => "    {$key['foreign_table']} {$parent}--{$children} {$table} : \"{$label}\"",
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, string>  $tables
     */
    private function polymorphicNote(Collection $tables): ?string
    {
        $pairs = $tables->flatMap(function (string $table): array {
            $names = collect(Schema::getColumns($table))->map(fn (array $column): string => $column['name']);

            return $names
                ->filter(fn (string $name): bool => str_ends_with($name, '_type'))
                ->mapWithKeys(fn (string $type): array => [$type => str($type)->beforeLast('_type')->append('_id')->value()])
                ->filter(fn (string $id): bool => $names->contains($id))
                ->map(fn (string $id, string $type): string => "- `{$table}.{$type}` and `{$table}.{$id}`")
                ->values()
                ->all();
        });

        if ($pairs->isEmpty()) {
            return null;
        }

        return "These polymorphic column pairs can point to any table, so the diagram draws no line for them:\n\n".$pairs->implode("\n");
    }

    private function type(string $name): string
    {
        return self::TYPES[$name] ?? $name;
    }
}
