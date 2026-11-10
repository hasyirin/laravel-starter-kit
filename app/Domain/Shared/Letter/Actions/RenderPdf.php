<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\User\Support\Preferences;
use Illuminate\Support\Traits\Localizable;
use Locale;
use Spatie\Browsershot\Browsershot;
use Spatie\LaravelPdf\Enums\Format;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

final class RenderPdf
{
    use Actionable;
    use Localizable;

    /**
     * @param  array<string, mixed>  $viewData
     */
    public function execute(string $view, array $viewData, Preferences $preferences, bool $draft = false): PdfBuilder
    {
        $html = context()->scope(
            fn (): string => $this->withLocale(
                (string) Locale::getPrimaryLanguage($preferences->locale),
                fn (): string => view()->first([$view], $viewData)->render(),
            ),
            hidden: ['timezone' => $preferences->timezone, 'locale' => $preferences->locale, 'letter_draft' => $draft],
        );

        return Pdf::html($html)
            ->format(Format::A4)
            ->withBrowsershot(fn (Browsershot $browsershot): Browsershot => $browsershot
                ->blockUrls(['http://', 'https://'])
                ->disableJavascript());
    }
}
