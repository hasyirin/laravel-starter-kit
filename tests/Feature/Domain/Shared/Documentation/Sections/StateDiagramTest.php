<?php

declare(strict_types=1);

use App\Domain\Shared\Documentation\Sections\StateDiagram;
use App\Domain\Shared\Documentation\Support\StateGraph;
use Tests\Fixtures\Models\Ticket;

test('the diagram shows the states, the default state, the steps, and the final states', function (): void {
    expect(new StateDiagram()->render(['model' => Ticket::class, 'field' => 'state']))->toBe(<<<'MD'
        ```mermaid
        stateDiagram-v2
            state "Approved" as approved
            state "In Review" as in_review
            state "Open" as open
            state "Turned Down" as rejected
            state "Returned" as returned
            [*] --> open
            open --> in_review: in-review
            returned --> in_review: in-review
            in_review --> returned: returned
            in_review --> approved: approve
            in_review --> rejected: rejected
            approved --> [*]
            rejected --> [*]
        ```
        MD);
});

test('a state section needs a model and its state field', function (array $options): void {
    expect(fn (): string => new StateDiagram()->render($options))->toThrow(InvalidArgumentException::class);
})->with([
    'no options' => [[]],
    'a class that is not a model' => [['model' => StateGraph::class, 'field' => 'state']],
    'a field that is not a state' => [['model' => Ticket::class, 'field' => 'title']],
]);
