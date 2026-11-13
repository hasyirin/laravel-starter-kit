<?php

declare(strict_types=1);

use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\Shared\Api\Models\ApiToken;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(new ApiClient()->getTable(), function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('enabled')->default(true);
            $table->json('allowed_ip_addresses');
            $table->boolean('allows_any_ip_address')->default(false);
            $table->unsignedInteger('rate_limit_per_minute')->default(60);
            $table->string('service')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();
        });

        DB::statement('create unique index api_clients_name_unique on api_clients (name) where deleted_at is null');
        DB::statement('create unique index api_clients_service_unique on api_clients (service) where deleted_at is null');

        Schema::create(new ApiToken()->getTable(), function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(ApiClient::class)->index()->constrained(new ApiClient()->getTable())->cascadeOnDelete();
            $table->string('token_hash', 64);
            $table->string('last_four', 4);
            $table->json('scopes');
            $table->timestampTz('expires_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->timestampTz('last_used_at')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();
        });

        DB::statement('create unique index api_tokens_token_hash_unique on api_tokens (token_hash) where deleted_at is null');
    }

    public function down(): void
    {
        Schema::dropIfExists(new ApiToken()->getTable());
        Schema::dropIfExists(new ApiClient()->getTable());
    }
};
