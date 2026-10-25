<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Local;

use App\Livewire\Pages\Shared\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Livewire\Attributes\Locked;

class ComponentDocs extends Page
{
    #[Locked]
    public ?string $page = null;

    /** @var array<string, mixed> */
    public array $form = [];

    public function mount(?string $page = null): void
    {
        abort_unless($page === null || $this->documents()->has($page), 404);

        $this->page = $page;
    }

    public function render(): View
    {
        $documents = $this->documents();

        view()->share('errors', (new ViewErrorBag)->put('default', new MessageBag([
            'form.taken_email' => (string) __('validation.unique', ['attribute' => 'email']),
        ])));

        return view('livewire.pages.local.component-docs', [
            'documents' => $documents,
            'parts' => $this->parts((string) file_get_contents(base_path('docs/components/'.($this->page ?? 'README').'.md'))),
        ])->title($this->page === null ? (string) __('docs.components') : (string) $documents->get($this->page));
    }

    /**
     * @return Collection<string, string>
     */
    private function documents(): Collection
    {
        return collect(glob(base_path('docs/components/*.md')) ?: [])
            ->reject(fn (string $path): bool => basename($path) === 'README.md')
            ->mapWithKeys(fn (string $path): array => [basename($path, '.md') => str((string) file_get_contents($path))->match('/^# (.+)$/m')->value()]);
    }

    /**
     * @return list<array{html: HtmlString}|array{preview: HtmlString, code: string, bindings: list<string>}>
     */
    private function parts(string $markdown): array
    {
        $html = str($markdown)->markdown()->replaceMatches(
            '/href="([\w-]+)\.md"/',
            fn (array $match): string => 'href="'.route('docs.components', $match[1] === 'README' ? [] : [$match[1]]).'"',
        );

        $pieces = preg_split('#<pre><code class="language-blade">(.*?)</code></pre>#s', $html->value(), flags: PREG_SPLIT_DELIM_CAPTURE) ?: [];

        return array_map(function (string $piece, int $index): array {
            if ($index % 2 === 0) {
                return ['html' => new HtmlString($piece)];
            }

            $code = trim(html_entity_decode($piece, ENT_QUOTES | ENT_HTML5));
            preg_match_all('/wire:model(?:\.[\w.]+)?="([^"]+)"/', $code, $bindings);

            return [
                'preview' => new HtmlString(Blade::render($code)),
                'code' => $code,
                'bindings' => array_values(array_unique($bindings[1])),
            ];
        }, $pieces, array_keys($pieces));
    }
}
