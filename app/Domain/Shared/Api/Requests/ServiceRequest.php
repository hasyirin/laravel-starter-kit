<?php

declare(strict_types=1);

namespace App\Domain\Shared\Api\Requests;

use Illuminate\Database\Eloquent\Model;
use Saloon\Http\Request;

abstract class ServiceRequest extends Request
{
    private ?Model $resource = null;

    public function about(Model $resource): static
    {
        $this->resource = $resource;

        return $this;
    }

    public function resource(): ?Model
    {
        return $this->resource;
    }
}
