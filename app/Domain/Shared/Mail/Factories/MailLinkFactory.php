<?php

declare(strict_types=1);

namespace App\Domain\Shared\Mail\Factories;

use App\Domain\Shared\Mail\Models\MailLink;
use App\Domain\Shared\Message\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MailLink>
 */
class MailLinkFactory extends Factory
{
    protected $model = MailLink::class;

    public function definition(): array
    {
        return [
            'message_id' => Message::factory(),
            'token' => Str::random(48),
            'url' => fake()->url(),
        ];
    }

    public function requiringAuth(): static
    {
        return $this->state([
            'url' => route('settings'),
            'requires_auth' => true,
        ]);
    }
}
