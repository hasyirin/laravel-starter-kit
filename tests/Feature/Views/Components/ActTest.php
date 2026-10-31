<?php

declare(strict_types=1);

use App\Domain\Shared\Actor\Enums\Act;
use App\Domain\Shared\Actor\Models\Action;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Organization\Models\Position;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use App\Domain\User\Support\Preferences;
use Dom\Element;
use Dom\HTMLDocument;
use Hasyirin\Actor\Facades\Actor;
use Illuminate\Support\Carbon;

function renderAct(string $template, ?Action $action = null): HTMLDocument
{
    return HTMLDocument::createFromString((string) test()->blade($template, ['action' => $action]), LIBXML_NOERROR);
}

function preparedBy(User $user): Action
{
    return Actor::act(User::factory()->create(), Act::Prepare, $user, new Carbon('2026-03-02 09:30:00'));
}

test('an act shows who did the action, their position at that time, and when', function (): void {
    $position = Position::factory()->create(['name' => 'Clerk']);
    $profile = Profile::factory()->for($position->organization)->for($position)->for(User::factory()->state(['name' => 'Ada Lovelace']))->create();
    $action = preparedBy($profile->user);

    $document = renderAct('<x-act :action="$action" />', $action);

    expect(trim($document->querySelector('dl > dt')?->textContent ?? ''))->toBe('Prepared by:')
        ->and(array_map(fn (Element $line): string => trim($line->textContent), iterator_to_array($document->querySelectorAll('dl > dd > p'))))
        ->toBe(['Ada Lovelace', 'Clerk', (string) Preferences::current()->datetime($action->acted_at)]);
});

test('an act shows the position of the profile that the action kept', function (): void {
    $position = Position::factory()->create(['name' => 'Clerk']);
    $profile = Profile::factory()->for($position->organization)->for($position)->create();
    $action = preparedBy($profile->user);
    $promotion = Position::factory()->for($position->organization)->create(['name' => 'Manager']);
    Profile::factory()->for($promotion->organization)->for($promotion)->for($profile->user)->create();

    expect(renderAct('<x-act :action="$action" />', Action::query()->find($action->id))->querySelector('dl > dd')?->textContent)
        ->toContain('Clerk')
        ->not->toContain('Manager');
});

test('an act shows the name of a user who was deleted', function (): void {
    $profile = Profile::factory()->for(User::factory()->state(['name' => 'Ada Lovelace']))->create();
    $action = preparedBy($profile->user);
    $profile->user->delete();

    expect(renderAct('<x-act :action="$action" />', Action::query()->find($action->id))->querySelector('dl > dd > p')?->textContent)->toBe('Ada Lovelace');
});

test('an act shows the name of the actor when the action has no profile', function (): void {
    $action = preparedBy(User::factory()->create(['name' => 'Ada Lovelace']));

    $document = renderAct('<x-act :action="$action" />', $action);

    expect($document->querySelector('dl > dd > p')?->textContent)->toBe('Ada Lovelace')
        ->and($document->querySelectorAll('dl > dd > p')->length)->toBe(2);
});

test('an act shows the name of a user without a profile who was deleted', function (): void {
    $user = User::factory()->create(['name' => 'Ada Lovelace']);
    $action = preparedBy($user);
    $user->delete();

    expect(renderAct('<x-act :action="$action" />', Action::query()->find($action->id))->querySelector('dl > dd > p')?->textContent)->toBe('Ada Lovelace');
});

test('an act shows no name for an actor without an actor name', function (): void {
    $action = Actor::act(User::factory()->create(), Act::Prepare, Organization::factory()->create());

    $lines = renderAct('<x-act :action="$action" />', $action)->querySelectorAll('dl > dd > p');

    expect($lines->length)->toBe(1)
        ->and($lines->item(0)?->textContent)->toBe((string) Preferences::current()->datetime($action->acted_at));
});

test('an act loads its profile, user, and position in three queries', function (): void {
    $position = Position::factory()->create();
    $action = preparedBy(Profile::factory()->for($position->organization)->for($position)->create()->user);
    $action = Action::query()->find($action->id);

    $this->expectsDatabaseQueryCount(3);

    renderAct('<x-act :action="$action" />', $action);
});

test('an act without a profile loads its actor in one query', function (): void {
    $action = Action::query()->find(preparedBy(User::factory()->create())->id);

    $this->expectsDatabaseQueryCount(1);

    renderAct('<x-act :action="$action" />', $action);
});

test('acts without a profile render from a query of many actions', function (): void {
    preparedBy(User::factory()->create(['name' => 'Ada Lovelace']));
    preparedBy(User::factory()->create(['name' => 'Grace Hopper']));

    $names = Action::query()->oldest('id')->get()
        ->map(fn (Action $action): ?string => renderAct('<x-act :action="$action" />', $action)->querySelector('dl > dd > p')?->textContent);

    expect($names->all())->toBe(['Ada Lovelace', 'Grace Hopper']);
});

test('an act without an action shows its slot', function (string $template): void {
    $document = renderAct($template);

    expect(trim($document->querySelector('dl > dt')?->textContent ?? ''))->toBe('Prepared by:')
        ->and(trim($document->querySelector('dl > dd')?->textContent ?? ''))->toBe('Not prepared yet.');
})->with([
    'a case' => '<x-act :name="'.Act::class.'::Prepare">Not prepared yet.</x-act>',
    'a value' => '<x-act name="prepare">Not prepared yet.</x-act>',
]);

test('an act without a name or an action renders nothing', function (): void {
    expect(trim((string) $this->blade('<x-act :action="null">Not prepared yet.</x-act>')))->toBeEmpty();
});

test('the attributes of an act reach its list', function (): void {
    expect(renderAct('<x-act name="prepare" class="mt-6" />')->querySelector('dl')?->classList->contains('mt-6'))->toBeTrue();
});
