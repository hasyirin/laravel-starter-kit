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
        $steps = $graph->steps();
        $default = $graph->default();

        return implode("\n", [
            '```mermaid',
            'stateDiagram-v2',
            ...array_map(fn (string $name, string $label): string => '    state "'.StateGraph::quote($label)."\" as {$name}", array_keys($states), $states),
            ...(in_array(null, array_column($steps, 'from'), true) ? ['    state "Any state" as '.StateGraph::ANY] : []),
            ...($default === null ? [] : ["    [*] --> {$default}"]),
            ...array_map(fn (array $step): string => '    '.($step['from'] ?? StateGraph::ANY)." --> {$step['to']}: {$step['step']}", $steps),
            ...array_map(fn (string $name): string => "    {$name} --> [*]", array_values(array_diff(array_keys($states), array_column($graph->transitions(), 'from')))),
            '```',
        ]);
    }
}
