<?php

declare(strict_types=1);

namespace App\Domain\Shared\Documentation\Sections;

use App\Domain\Shared\Documentation\Contracts\Section;
use App\Domain\Shared\Documentation\Support\MarkdownTable;
use App\Domain\Shared\Documentation\Support\StateGraph;

class Transitions implements Section
{
    public function render(array $options): string
    {
        $graph = StateGraph::fromOptions($options);
        $states = $graph->states();

        $rows = array_map(function (array $transition) use ($graph, $states): array {
            $permission = $graph->permission($transition['step']);

            return [
                $states[$transition['from']] ?? $transition['from'],
                $states[$transition['to']] ?? $transition['to'],
                $transition['step'],
                $permission === null ? 'Not in config/system.php' : "`{$permission}`",
                $permission === null ? '' : implode(', ', $graph->roles($permission)),
            ];
        }, $graph->transitions());

        $table = MarkdownTable::render(['From', 'To', 'Step', 'Permission', 'Roles'], $rows);

        return implode("\n\n", array_filter([$table, $graph->bypassNote()]));
    }
}
