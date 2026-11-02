<?php

declare(strict_types=1);

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\Fixtures\Livewire\NoteModal;
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

    $this->actingAs(User::factory()->create());
});

function savedToast(string $key): Closure
{
    return fn (string $name, array $params): bool => $params['slots']['text'] === __($key, ['record' => 'Note']);
}

test('showing a record opens the modal and fills the form', function (): void {
    $notebook = Notebook::query()->create();
    $note = $notebook->notes()->create(['title' => 'Leak']);

    Livewire::test(NoteModal::class, ['notebook' => $notebook])
        ->call('show', $note->id)
        ->assertSet('open', true)
        ->assertSet('recordId', $note->id)
        ->assertSet('title', 'Leak')
        ->assertSeeHtml('data-note-form');
});

test('a closed modal renders no form', function (): void {
    Livewire::test(NoteModal::class, ['notebook' => Notebook::query()->create()])
        ->assertDontSeeHtml('data-note-form');
});

test('showing without an id starts a new record and drops the last one', function (): void {
    $notebook = Notebook::query()->create();
    $note = $notebook->notes()->create(['title' => 'Leak']);

    Livewire::test(NoteModal::class, ['notebook' => $notebook])
        ->call('show', $note->id)
        ->call('show')
        ->assertSet('recordId', null)
        ->assertSet('title', '');
});

test('a record outside the query gives 404', function (): void {
    $other = Notebook::query()->create()->notes()->create(['title' => 'Other']);

    Livewire::test(NoteModal::class, ['notebook' => Notebook::query()->create()])
        ->call('show', $other->id)
        ->assertNotFound();
});

test('showing a record without the view ability gives 403', function (): void {
    $notebook = Notebook::query()->create();
    $note = $notebook->notes()->create(['title' => 'Leak', 'hidden' => true]);

    Livewire::test(NoteModal::class, ['notebook' => $notebook])
        ->call('show', $note->id)
        ->assertForbidden();
});

test('starting a new record without the create ability gives 403', function (): void {
    Livewire::test(NoteModal::class, ['notebook' => Notebook::query()->create(['accepts_notes' => false])])
        ->call('show')
        ->assertForbidden();
});

test('a closed modal keeps no check on a record that was deleted', function (): void {
    $notebook = Notebook::query()->create();
    $note = $notebook->notes()->create(['title' => 'Leak']);

    $modal = Livewire::test(NoteModal::class, ['notebook' => $notebook])
        ->call('show', $note->id)
        ->set('open', false);

    $note->forceDelete();

    $modal->call('$refresh')->assertOk();
});

test('saving a new record creates it under the parent, tells the page, and closes', function (): void {
    $notebook = Notebook::query()->create();

    Livewire::test(NoteModal::class, ['notebook' => $notebook])
        ->call('show')
        ->set('title', 'Leak')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched(NoteModal::event())
        ->assertDispatched('toast-show', savedToast('common/records.created'))
        ->assertSet('open', false);

    expect($notebook->notes()->sole()->title)->toBe('Leak');
});

test('saving a record updates it and shows the updated toast', function (): void {
    $notebook = Notebook::query()->create();
    $note = $notebook->notes()->create(['title' => 'Leak']);

    Livewire::test(NoteModal::class, ['notebook' => $notebook])
        ->call('show', $note->id)
        ->set('title', 'Crack')
        ->call('save')
        ->assertDispatched('toast-show', savedToast('common/records.updated'));

    expect($note->refresh()->title)->toBe('Crack');
});

test('invalid input keeps the modal open and the errors are gone on reopen', function (): void {
    Livewire::test(NoteModal::class, ['notebook' => Notebook::query()->create()])
        ->call('show')
        ->call('save')
        ->assertHasErrors('title')
        ->assertSet('open', true)
        ->assertNotDispatched(NoteModal::event())
        ->call('show')
        ->assertHasNoErrors();
});

test('saving without the write ability gives 403', function (): void {
    $notebook = Notebook::query()->create();
    $note = $notebook->notes()->create(['title' => 'Leak']);

    $modal = Livewire::test(NoteModal::class, ['notebook' => $notebook])->call('show', $note->id);

    $note->update(['locked' => true]);

    $modal->set('title', 'Crack')->call('save')->assertForbidden();

    expect($note->refresh()->title)->toBe('Leak');
});

test('saving a closed modal gives 403', function (): void {
    $notebook = Notebook::query()->create();
    $note = $notebook->notes()->create(['title' => 'Leak']);

    Livewire::test(NoteModal::class, ['notebook' => $notebook])
        ->call('show', $note->id)
        ->set('title', 'Crack')
        ->set('open', false)
        ->call('save')
        ->assertForbidden();

    expect($note->refresh()->title)->toBe('Leak');
});

test('a second save after the modal closed adds no record', function (): void {
    $notebook = Notebook::query()->create();
    $note = $notebook->notes()->create(['title' => 'Leak']);

    Livewire::test(NoteModal::class, ['notebook' => $notebook])
        ->call('show', $note->id)
        ->set('title', 'Crack')
        ->call('save')
        ->call('save')
        ->assertForbidden();

    expect($notebook->notes()->count())->toBe(1);
});

test('the browser cannot open the form without the show checks', function (): void {
    Livewire::test(NoteModal::class, ['notebook' => Notebook::query()->create(['accepts_notes' => false])])
        ->set('open', true)
        ->assertSet('open', false)
        ->assertDontSeeHtml('data-note-form');
});

test('the policy for a new record sees the parent on the save request', function (): void {
    $notebook = Notebook::query()->create();

    $modal = Livewire::test(NoteModal::class, ['notebook' => $notebook])->call('show');

    $notebook->update(['accepts_notes' => false]);

    $modal->set('title', 'Leak')->call('save')->assertForbidden();

    expect($notebook->notes()->exists())->toBeFalse();
});

test('a disabled modal cannot save and the view knows it', function (): void {
    $notebook = Notebook::query()->create();

    Livewire::test(NoteModal::class, ['notebook' => $notebook, 'disabled' => true])
        ->call('show')
        ->assertViewHas('disabled', true)
        ->assertDontSeeHtml('data-note-save')
        ->set('title', 'Leak')
        ->call('save')
        ->assertForbidden();
});

test('the view is disabled without the write ability', function (): void {
    $notebook = Notebook::query()->create();
    $note = $notebook->notes()->create(['title' => 'Leak', 'locked' => true]);

    Livewire::test(NoteModal::class, ['notebook' => $notebook])
        ->call('show', $note->id)
        ->assertViewHas('disabled', true);
});

test('the browser cannot point the modal at another record', function (): void {
    Livewire::test(NoteModal::class, ['notebook' => Notebook::query()->create()])
        ->set('recordId', 1);
})->throws(CannotUpdateLockedPropertyException::class);

test('each modal class has its own saved event', function (): void {
    $otherModal = new class extends NoteModal {};

    expect($otherModal::event())->not->toBe(NoteModal::event());
});
