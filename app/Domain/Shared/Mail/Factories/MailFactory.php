<?php

declare(strict_types=1);

namespace App\Domain\Shared\Mail\Factories;

use App\Domain\Shared\Mail\Enums\MailStatus;
use App\Domain\Shared\Mail\Models\Mail;
use Illuminate\Database\Eloquent\Factories\Factory;
use Symfony\Component\Mailer\Exception\TransportException;

/**
 * @extends Factory<Mail>
 */
class MailFactory extends Factory
{
    protected $model = Mail::class;

    public function definition(): array
    {
        return [
            'recipient_email' => fake()->safeEmail(),
            'recipient_name' => fake()->name(),
            'subject' => fake()->sentence(),
            'html_body' => '<p>'.fake()->paragraph().'</p>',
            'text_body' => fake()->paragraph(),
            'status' => MailStatus::Sent,
            'sent_at' => now(),
        ];
    }

    public function sending(): static
    {
        return $this->state([
            'status' => MailStatus::Sending,
            'sent_at' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status' => MailStatus::Failed,
            'error' => ['type' => TransportException::class, 'code' => 0, 'message' => 'Connection could not be established.'],
            'sent_at' => null,
            'failed_at' => now(),
        ]);
    }
}
