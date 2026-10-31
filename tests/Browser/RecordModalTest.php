<?php

declare(strict_types=1);

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\Fixtures\Livewire\NoteModal;
use Tests\Fixtures\Livewire\NoteModalPage;
use Tests\Fixtures\Models\Note;
use Tests\Fixtures\Models\Notebook;
use Tests\Fixtures\Policies\NotePolicy;

beforeEach(function (): void {
    Schema::create('notebooks', function (Blueprint $table): void {
        $table->id();
        $table->boolean('accepts_notes')->default(true);
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('notes', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('notebook_id');
        $table->string('title');
        $table->boolean('hidden')->default(false);
        $table->boolean('locked')->default(false);
        $table->timestamps();
        $table->softDeletes();
    });

    Relation::morphMap(['notebook' => Notebook::class, 'note' => Note::class]);
    Gate::policy(Note::class, NotePolicy::class);
    Livewire::component('note-modal', NoteModal::class);
    Route::livewire('/browser/notes', NoteModalPage::class)->middleware('web');

    Notebook::query()->create();
    $this->actingAs(User::factory()->create());
});

function noteModalScript(string $body): string
{
    $helpers = <<<'SCRIPT'
        const dialog = () => document.querySelector('[data-note-modal] dialog');
        const form = () => document.querySelector('[data-note-form]');
        const opened = () => dialog().open && form()?.checkVisibility() === true;
        SCRIPT;

    return browserScript("{$helpers}\n{$body}");
}

test('the modal opens with its form, closes with Esc, and opens again', function (): void {
    $page = visit('/browser/notes');

    expect($page->script(noteModalScript(<<<'SCRIPT'
        if (dialog().open || form()) {
            return 'open at the start';
        }

        document.querySelector('[data-open]').click();

        if (! await until(opened)) {
            return 'first open failed';
        }

        dialog().dispatchEvent(new Event('cancel', { cancelable: true }));
        dialog().close();

        if (! await until(() => ! dialog().open)) {
            return 'close failed';
        }

        await new Promise((resolve) => setTimeout(resolve, 300));
        document.querySelector('[data-open]').click();

        return await until(opened) ? 'ok' : 'second open failed';
        SCRIPT)))->toBe('ok');
});

test('saving closes the modal and tells the page', function (): void {
    $page = visit('/browser/notes');

    expect($page->script(noteModalScript(<<<'SCRIPT'
        document.querySelector('[data-open]').click();
        await until(opened);

        const title = document.querySelector('[data-note-title]');
        title.value = 'Leak';
        title.dispatchEvent(new Event('input', { bubbles: true }));
        document.querySelector('[data-note-save]').click();

        return await until(() => ! dialog().open && document.querySelector('[data-saves]').textContent.trim() === '1');
        SCRIPT)))->toBeTrue();
    expect(Note::query()->sole()->title)->toBe('Leak');
});
