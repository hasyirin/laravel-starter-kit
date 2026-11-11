<?php

declare(strict_types=1);

namespace App\Domain\Shared\Message\Factories;

use App\Domain\Shared\Message\Enums\MessageStatus;
use App\Domain\Shared\Message\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;
use Symfony\Component\Mailer\Exception\TransportException;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'channel' => 'mail',
            'recipient_address' => fake()->safeEmail(),
            'recipient_name' => fake()->name(),
            'subject' => fake()->sentence(),
            'html_body' => '<p>'.fake()->paragraph().'</p>',
            'text_body' => fake()->paragraph(),
            'status' => MessageStatus::Sent,
            'sent_at' => now(),
        ];
    }

    public function sms(): static
    {
        return $this->state([
            'channel' => 'sms',
            'recipient_address' => fake()->numerify('+6012#######'),
            'recipient_name' => null,
            'subject' => null,
            'html_body' => null,
            'text_body' => fake()->sentence(),
        ]);
    }

    public function tracked(): static
    {
        return $this->state(['tracked' => true]);
    }

    public function sending(): static
    {
        return $this->state([
            'status' => MessageStatus::Sending,
            'sent_at' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status' => MessageStatus::Failed,
            'error' => ['type' => TransportException::class, 'code' => 0, 'message' => 'Connection could not be established.'],
            'sent_at' => null,
            'failed_at' => now(),
        ]);
    }
}
