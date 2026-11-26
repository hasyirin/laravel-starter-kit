<?php

declare(strict_types=1);

namespace App\Domain\User\Factories;

use App\Domain\User\Models\User;
use App\Domain\User\Models\UserSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<UserSession>
 */
class UserSessionFactory extends Factory
{
    protected $model = UserSession::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'session_id' => Str::random(40),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'last_activity_at' => now(),
            'signed_in_at' => now(),
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(['user_id' => $user->id]);
    }

    public function impersonatedBy(User $impersonator): static
    {
        return $this->state(['impersonator_id' => $impersonator->id]);
    }
}
