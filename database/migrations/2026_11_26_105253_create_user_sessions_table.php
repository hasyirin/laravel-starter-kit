<?php

declare(strict_types=1);

use App\Domain\User\Models\User;
use App\Domain\User\Models\UserSession;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(new UserSession()->getTable(), function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(User::class)->index()->constrained(new User()->getTable())->cascadeOnDelete();
            $table->foreignId('impersonator_id')->nullable()->index()->constrained(new User()->getTable())->cascadeOnDelete();
            $table->string('session_id');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestampTz('last_activity_at')->index();
            $table->timestampTz('signed_in_at')->index();
            $table->timestampsTz();
            $table->softDeletesTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(new UserSession()->getTable());
    }
};
