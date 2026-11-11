<?php

declare(strict_types=1);

use App\Domain\Shared\Mail\Models\MailLink;
use App\Domain\Shared\Message\Models\Message;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(new Message()->getTable(), function (Blueprint $table): void {
            $table->id();
            $table->uuid('notification_id')->nullable()->index();
            $table->string('notification_type')->nullable();
            $table->longText('payload')->nullable();
            $table->nullableMorphs('notifiable');
            $table->nullableMorphs('resource');
            $table->string('recipient_address')->nullable();
            $table->string('recipient_name')->nullable();
            $table->json('cc')->nullable();
            $table->json('bcc')->nullable();
            $table->string('intercepted_to')->nullable();
            $table->text('subject')->nullable();
            $table->longText('html_body')->nullable();
            $table->longText('text_body')->nullable();
            $table->string('provider_id')->nullable();
            $table->string('status');
            $table->json('error')->nullable();
            $table->boolean('tracked')->default(false);
            $table->string('tracking_token', 64);
            $table->unsignedInteger('open_count')->default(0);
            $table->timestampTz('opened_at')->nullable();
            $table->timestampTz('clicked_at')->nullable();
            $table->timestampTz('viewed_at')->nullable();
            $table->timestampTz('sent_at')->nullable();
            $table->timestampTz('failed_at')->nullable();
            $table->foreignId('resend_of_id')->nullable()->index()->constrained(new Message()->getTable())->nullOnDelete();
            $table->timestampsTz();
            $table->softDeletesTz();
        });

        DB::statement('create unique index messages_tracking_token_unique on messages (tracking_token) where deleted_at is null');

        Schema::create(new MailLink()->getTable(), function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Message::class)->index()->constrained(new Message()->getTable())->cascadeOnDelete();
            $table->string('token', 64);
            $table->text('url');
            $table->boolean('requires_auth')->default(false);
            $table->timestampTz('clicked_at')->nullable();
            $table->timestampTz('viewed_at')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();
        });

        DB::statement('create unique index mail_links_token_unique on mail_links (token) where deleted_at is null');
    }

    public function down(): void
    {
        Schema::dropIfExists(new MailLink()->getTable());
        Schema::dropIfExists(new Message()->getTable());
    }
};
