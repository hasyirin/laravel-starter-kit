<?php

declare(strict_types=1);

namespace App\Domain\Shared\Documentation\Sections;

use App\Domain\Shared\Documentation\Contracts\Section;
use App\Domain\Shared\Documentation\Support\StateGraph;

class StateDiagram implements Section
{
    public function render(array $options): string
    {
        $graph = StateGraph::fromOptions($options);
        $states = $graph->states();
        $transitions = $graph->transitions();
        $default = $graph->default();

        return implode("\n", [
            '```mermaid',
            'stateDiagram-v2',
            ...array_map(fn (string $name, string $label): string => '    state "'.str_replace('"', '#quot;', $label)."\" as {$name}", array_keys($states), $states),
            ...($default === null ? [] : ["    [*] --> {$default}"]),
            ...array_map(fn (array $transition): string => "    {$transition['from']} --> {$transition['to']}: {$transition['step']}", $transitions),
            ...array_map(fn (string $name): string => "    {$name} --> [*]", array_values(array_diff(array_keys($states), array_column($transitions, 'from')))),
            '```',
        ]);
    }
}
