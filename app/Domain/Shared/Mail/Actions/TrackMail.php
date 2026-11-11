<?php

declare(strict_types=1);

namespace App\Domain\Shared\Mail\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Mail\Models\Mail;
use App\Domain\Shared\Mail\Models\MailLink;
use App\Domain\Shared\Mail\Support\SensitiveValues;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Email;

class TrackMail
{
    use Actionable;

    /** @var Collection<string, MailLink> */
    private Collection $links;

    private ?SensitiveValues $sensitive = null;

    public function __construct()
    {
        $this->links = new Collection;
    }

    public function execute(Mail $mail, Email $message, SensitiveValues $sensitive): void
    {
        $this->links = new Collection;
        $this->sensitive = $sensitive;

        $html = $message->getHtmlBody();
        $text = $message->getTextBody();

        if (is_string($html)) {
            $message->html($this->addPixel($mail, $this->wrapHtmlLinks($mail, $html)));
        }

        if (is_string($text)) {
            $message->text($this->wrapTextLinks($mail, $text));
        }
    }

    private function wrapHtmlLinks(Mail $mail, string $html): string
    {
        return (string) preg_replace_callback('/(<a\b[^>]*?(?<![\w-])href\s*=\s*)(["\'])(.*?)\2/is', function (array $match) use ($mail): string {
            $wrapped = $this->wrap($mail, html_entity_decode($match[3], ENT_QUOTES | ENT_HTML5));

            if ($wrapped === null) {
                return $match[0];
            }

            return $match[1].$match[2].e($wrapped).$match[2];
        }, $html);
    }

    private function wrapTextLinks(Mail $mail, string $text): string
    {
        return (string) preg_replace_callback('~https?://[^\s<>"\'\])]+~i', function (array $match) use ($mail): string {
            $url = rtrim($match[0], '.,;:!?');

            return ($this->wrap($mail, $url) ?? $url).substr($match[0], strlen($url));
        }, $text);
    }

    private function addPixel(Mail $mail, string $html): string
    {
        $pixel = '<img src="'.e(route('mail.open', $mail->tracking_token)).'" width="1" height="1" alt="" style="border:0;width:1px;height:1px;">';

        if (str($html)->lower()->contains('</body>')) {
            return (string) preg_replace('~</body>~i', $pixel.'</body>', $html, 1);
        }

        return $html.$pixel;
    }

    private function wrap(Mail $mail, string $url): ?string
    {
        if (! $this->trackable($url)) {
            return null;
        }

        $link = $this->links->get($url) ?? $mail->links()->where('url', $url)->first() ?? $mail->links()->create([
            'url' => $url,
            'token' => Str::random(48),
            'requires_auth' => $this->requiresAuth($url),
        ]);

        $this->links->put($url, $link);

        return route('mail.link', $link->token);
    }

    private function trackable(string $url): bool
    {
        if (! str($url)->lower()->startsWith(['http://', 'https://'])) {
            return false;
        }

        if (in_array($this->route($url)?->getName(), ['mail.link', 'mail.open'], true)) {
            return false;
        }

        return $this->sensitive?->appearIn($url) !== true;
    }

    private function requiresAuth(string $url): bool
    {
        $route = $this->route($url);

        return $route instanceof Route && collect($route->gatherMiddleware())
            ->contains(fn (mixed $middleware): bool => is_string($middleware) && ($middleware === 'auth' || str_starts_with($middleware, 'auth:')));
    }

    private function route(string $url): ?Route
    {
        $request = Request::create($url);

        if ($request->getHost() !== parse_url(config()->string('app.url'), PHP_URL_HOST)) {
            return null;
        }

        return collect(resolve(Router::class)->getRoutes()->get($request->getMethod()))
            ->reject(fn (Route $route): bool => $route->isFallback)
            ->first(fn (Route $route): bool => $route->matches($request));
    }
}
