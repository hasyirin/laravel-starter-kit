<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use Filament\Actions\Action;
use Override;

class ViewAction extends Action
{
    #[Override]
    public static function getDefaultName(): string
    {
        return 'view';
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->icon('heroicon-m-eye')
            ->label((string) __('common/buttons.view'))
            ->color('primary');
    }
}
