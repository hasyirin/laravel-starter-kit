<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use Filament\Actions\Action;
use Override;

class EditAction extends Action
{
    #[Override]
    public static function getDefaultName(): string
    {
        return 'edit';
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->icon('heroicon-m-pencil-square')
            ->label((string) __('common/buttons.edit'))
            ->color('gray');
    }
}
