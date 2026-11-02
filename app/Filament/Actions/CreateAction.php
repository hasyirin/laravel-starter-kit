<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use Filament\Actions\Action;
use Override;

class CreateAction extends Action
{
    #[Override]
    public static function getDefaultName(): string
    {
        return 'create';
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->icon('heroicon-m-plus')
            ->label((string) __('common/buttons.add'))
            ->color('primary');
    }
}
