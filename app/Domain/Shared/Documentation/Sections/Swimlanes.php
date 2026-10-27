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
        $steps = $graph->steps();
        $lanes = [];

        foreach ($steps as $step) {
            $permission = $graph->permission($step['step']);
            $roles = $permission === null ? [] : $graph->roles($permission);
            $lanes[$roles === [] ? self::NO_ROLE : implode(', ', $roles)][] = $step;
        }

        uksort($lanes, fn (string $a, string $b): int => [$a === self::NO_ROLE, $a] <=> [$b === self::NO_ROLE, $b]);

        $lines = ['```mermaid', 'flowchart LR'];

        foreach (array_values($lanes) as $index => $laneSteps) {
            $lines[] = '    subgraph lane_'.($index + 1).'["'.StateGraph::quote(array_keys($lanes)[$index]).'"]';

            foreach ($laneSteps as $step) {
                $label = str($step['step'])->headline()->value().($step['from'] === null ? ' (from any state)' : '');
                $lines[] = '        '.$this->id($step).'["'.StateGraph::quote($label).'"]';
            }

            $lines[] = '    end';
        }

        foreach ($steps as $from) {
            foreach ($steps as $to) {
                if ($to['from'] !== null && $from['to'] === $to['from']) {
                    $lines[] = '    '.$this->id($from).' --> '.$this->id($to);
                }
            }
        }

        $lines[] = '```';

        return implode("\n\n", array_filter([implode("\n", $lines), $graph->bypassNote()]));
    }

    /**
     * @param  array{from: string|null, to: string, step: string}  $step
     */
    private function id(array $step): string
    {
        return ($step['from'] ?? StateGraph::ANY)."__{$step['to']}";
    }
}
