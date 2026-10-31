<?php

declare(strict_types=1);

namespace App\Domain\Shared\Actor\Contracts\Models;

interface HasActorName
{
    public function getActorName(): string;
}
