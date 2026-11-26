<?php

declare(strict_types=1);

use App\Domain\User\Models\SignInEntry;
use App\Domain\User\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(new SignInEntry()->getTable(), function (Blueprint $table): void {
            $table->id();
            $table->string('event');
            $table->string('result')->nullable();
            $table->string('method')->nullable();
            $table->boolean('two_factor')->default(false);
            $table->foreignIdFor(User::class)->nullable()->index()->constrained(new User()->getTable())->nullOnDelete();
            $table->foreignId('impersonator_id')->nullable()->index()->constrained(new User()->getTable())->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('trace_id', 128)->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index('created_at');
            $table->index(['user_id', 'created_at']);
            $table->index(['result', 'created_at']);
            $table->index(['ip_address', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(new SignInEntry()->getTable());
    }
};
