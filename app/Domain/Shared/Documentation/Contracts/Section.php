<?php

declare(strict_types=1);

namespace App\Domain\Shared\Documentation\Contracts;

interface Section
{
    /**
     * @param  array<string, string>  $options
     */
    public function render(array $options): string;
}
