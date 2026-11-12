<?php

declare(strict_types=1);

test('a browser script that waits for more than 1 second runs only once', function (): void {
    $page = visit('/docs/components/card');

    $runs = $page->script(browserScript(<<<'SCRIPT'
        window.scriptRuns = (window.scriptRuns ?? 0) + 1;
        await new Promise((resolve) => setTimeout(resolve, 1200));

        return window.scriptRuns;
        SCRIPT));

    expect($runs)->toBe(1);
});
