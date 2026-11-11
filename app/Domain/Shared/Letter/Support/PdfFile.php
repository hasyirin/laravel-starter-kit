<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Support;

use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class PdfFile
{
    public function __construct(public string $name, public string $content) {}

    public static function named(?string $referenceNumber, string $fallback, string $content): self
    {
        $slug = str($referenceNumber ?? '')
            ->replace(['/', '\\'], '-')
            ->slug()
            ->value();

        return new self(($slug === '' ? $fallback : $slug).'.pdf', $content);
    }

    public function download(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            echo $this->content;
        }, $this->name, ['Content-Type' => 'application/pdf']);
    }

    public function inline(): Response
    {
        return response($this->content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$this->name.'"',
        ]);
    }
}
