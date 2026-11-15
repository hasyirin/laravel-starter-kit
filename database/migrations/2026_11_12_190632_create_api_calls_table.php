<?php

declare(strict_types=1);

use App\Domain\Shared\Api\Models\ApiCall;
use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\Shared\Api\Models\ApiToken;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(new ApiCall()->getTable(), function (Blueprint $table): void {
            $table->id();
            $table->string('direction');
            $table->foreignIdFor(ApiClient::class)->nullable()->index()->constrained(new ApiClient()->getTable())->nullOnDelete();
            $table->string('api_client_name')->nullable();
            $table->foreignIdFor(ApiToken::class)->nullable()->index()->constrained(new ApiToken()->getTable())->nullOnDelete();
            $table->string('service')->nullable();
            $table->nullableMorphs('resource');
            $table->string('method', 10);
            $table->text('url');
            $table->string('route_name')->nullable();
            $table->unsignedSmallInteger('status')->nullable();
            $table->string('outcome');
            $table->unsignedInteger('duration_ms');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('trace_id', 128)->nullable();
            $table->unsignedBigInteger('request_size')->nullable();
            $table->unsignedBigInteger('response_size')->nullable();
            $table->longText('request_body')->nullable();
            $table->longText('response_body')->nullable();
            $table->text('error')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index('created_at');
            $table->index(['direction', 'created_at']);
            $table->index(['service', 'created_at']);
            $table->index(['outcome', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(new ApiCall()->getTable());
    }
};
