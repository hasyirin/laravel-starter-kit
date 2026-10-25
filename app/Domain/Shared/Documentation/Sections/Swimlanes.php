<?php

declare(strict_types=1);

namespace App\Domain\Shared\Documentation\Sections;

use App\Domain\Shared\Documentation\Contracts\Section;
use App\Domain\Shared\Documentation\Support\StateGraph;

class Swimlanes implements Section
{
    private const string NO_ROLE = 'No role';

    public function render(array $options): string
    {
        $graph = StateGraph::fromOptions($options);
        $transitions = $graph->transitions();
        $lanes = [];

        foreach ($transitions as $transition) {
            $permission = $graph->permission($transition['step']);
            $roles = $permission === null ? [] : $graph->roles($permission);
            $lanes[$roles === [] ? self::NO_ROLE : implode(', ', $roles)][] = $transition;
        }

        uksort($lanes, fn (string $a, string $b): int => [$a === self::NO_ROLE, $a] <=> [$b === self::NO_ROLE, $b]);

        $lines = ['```mermaid', 'flowchart LR'];

        foreach (array_values($lanes) as $index => $steps) {
            $lines[] = '    subgraph lane_'.($index + 1).'["'.array_keys($lanes)[$index].'"]';

            foreach ($steps as $step) {
                $lines[] = '        '.$this->id($step).'["'.str($step['step'])->headline().'"]';
            }

            $lines[] = '    end';
        }

        foreach ($transitions as $from) {
            foreach ($transitions as $to) {
                if ($from['to'] === $to['from']) {
                    $lines[] = '    '.$this->id($from).' --> '.$this->id($to);
                }
            }
        }

        $lines[] = '```';

        return implode("\n\n", array_filter([implode("\n", $lines), $graph->bypassNote()]));
    }

    /**
     * @param  array{from: string, to: string, step: string}  $transition
     */
    private function id(array $transition): string
    {
        return "{$transition['from']}__{$transition['to']}";
    }
}
