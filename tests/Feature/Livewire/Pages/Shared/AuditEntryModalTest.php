<?php

declare(strict_types=1);

use App\Domain\User\Models\User;
use Livewire\Livewire;
use Tests\Fixtures\Livewire\DescribedAuditEntryModal;
use Tests\Fixtures\Livewire\PlainAuditEntryModal;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
    $this->entry = activity()->event('reminded')->withProperties(['count' => 3])->log('reminded');
});

test('an entry without changed fields shows the description of the modal', function (): void {
    Livewire::test(DescribedAuditEntryModal::class)
        ->call('show', $this->entry->id)
        ->assertSeeHtml('Sent &lt;b&gt;3&lt;/b&gt; reminders.')
        ->assertDontSee('No fields changed.');
});

test('an entry without changed fields shows that no field changed when the modal gives no description', function (): void {
    Livewire::test(PlainAuditEntryModal::class)
        ->call('show', $this->entry->id)
        ->assertSee('No fields changed.');
});

test('an entry with changed fields shows its changes, not the description', function (): void {
    $entry = activity()->event('reminded')->withProperties(['count' => 3])->log('reminded');
    $entry->attribute_changes = collect(['old' => ['name' => 'Alpha'], 'attributes' => ['name' => 'Beta']]);
    $entry->save();

    Livewire::test(DescribedAuditEntryModal::class)
        ->call('show', $entry->id)
        ->assertSee('Alpha')
        ->assertSee('Beta')
        ->assertDontSee('Sent')
        ->assertDontSee('No fields changed.');
});
