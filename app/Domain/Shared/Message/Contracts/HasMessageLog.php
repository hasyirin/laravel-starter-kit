<?php

declare(strict_types=1);

namespace App\Domain\Shared\Message\Contracts;

use Illuminate\Database\Eloquent\Model;
use Throwable;

interface HasMessageLog
{
    public ?int $resendOfId { get; set; }

    public ?string $resendTo { get; set; }

    public function tracked(object $notifiable): bool;

    /**
     * Markdown changes a value with characters such as * or _, so the log cannot find it in the HTML body.
     * Put such a value in an HtmlString or in a code span.
     *
     * @return list<string>
     */
    public function sensitive(object $notifiable): array;

    public function about(): ?Model;

    public function failed(Throwable $exception): void;
}
