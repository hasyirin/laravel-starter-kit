<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Flux\Flux;
use Illuminate\Database\Eloquent\Model;
use LogicException;
use Override;

class DeleteAction extends Action
{
    /**
     * @var class-string|null
     */
    protected ?string $deleteAction = null;

    #[Override]
    public static function getDefaultName(): string
    {
        return 'delete';
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->icon(Heroicon::Trash)
            ->label((string) __('common/buttons.delete'))
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading((string) __('common/records.delete'))
            ->modalSubmitActionLabel((string) __('common/buttons.delete'))
            ->hidden(fn (Model $record): bool => method_exists($record, 'trashed') && $record->trashed())
            ->action(function (Model $record): void {
                $this->run($record);

                Flux::toast(variant: 'success', text: __('common/records.deleted'));
            });
    }

    /**
     * @param  class-string  $deleteAction
     */
    public function through(string $deleteAction): static
    {
        $this->deleteAction = $deleteAction;

        return $this;
    }

    private function run(Model $record): void
    {
        throw_if($this->deleteAction === null, LogicException::class, self::class.' needs an Action. Call through().');

        // @phpstan-ignore method.nonObject (each Action types its own execute() parameter, so no shared contract fits)
        resolve($this->deleteAction)->execute($record);
    }
}
