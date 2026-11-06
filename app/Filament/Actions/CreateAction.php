<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use Filament\Actions\Action;
use Illuminate\Contracts\Support\Htmlable;
use LogicException;
use Override;

class CreateAction extends Action
{
    #[Override]
    public static function getDefaultName(): string
    {
        return 'create';
    }

    #[Override]
    public function getLabel(): string|Htmlable|null
    {
        throw_if($this->label === null, LogicException::class, 'Give the create action the singular name of its record as its label, such as "User".');

        return parent::getLabel();
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->icon('heroicon-m-plus')
            ->color('primary');
    }
}
