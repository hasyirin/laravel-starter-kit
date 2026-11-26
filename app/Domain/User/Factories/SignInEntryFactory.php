<?php

declare(strict_types=1);

namespace App\Domain\User\Factories;

use App\Domain\User\Enums\SignInEvent;
use App\Domain\User\Enums\SignInMethod;
use App\Domain\User\Enums\SignInResult;
use App\Domain\User\Models\SignInEntry;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SignInEntry>
 */
class SignInEntryFactory extends Factory
{
    protected $model = SignInEntry::class;

    public function definition(): array
    {
        return [
            'event' => SignInEvent::SignIn,
            'result' => SignInResult::SignedIn,
            'method' => SignInMethod::Password,
            'two_factor' => false,
            'user_id' => User::factory(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'trace_id' => fake()->uuid(),
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(['user_id' => $user->id]);
    }

    public function failed(SignInResult $result = SignInResult::WrongPassword): static
    {
        $failed = $this->state([
            'event' => SignInEvent::FailedSignIn,
            'result' => $result,
        ]);

        if ($result !== SignInResult::NoMatchingUser) {
            return $failed;
        }

        return $failed->state(['user_id' => null]);
    }

    public function signOut(): static
    {
        return $this->state([
            'event' => SignInEvent::SignOut,
            'result' => null,
            'method' => null,
        ]);
    }

    public function impersonationStarted(): static
    {
        return $this->impersonation(SignInEvent::ImpersonationStarted);
    }

    public function impersonationStopped(): static
    {
        return $this->impersonation(SignInEvent::ImpersonationStopped);
    }

    public function sessionEnded(): static
    {
        return $this->state([
            'event' => SignInEvent::SessionEnded,
            'result' => null,
            'method' => null,
            'ended_by_id' => User::factory(),
        ]);
    }

    public function sessionExpired(): static
    {
        return $this->state([
            'event' => SignInEvent::SessionExpired,
            'result' => null,
            'method' => null,
        ]);
    }

    private function impersonation(SignInEvent $event): static
    {
        return $this->state([
            'event' => $event,
            'result' => null,
            'method' => null,
            'impersonator_id' => User::factory(),
        ]);
    }
}
