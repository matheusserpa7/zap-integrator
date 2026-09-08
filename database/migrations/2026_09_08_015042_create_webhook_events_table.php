<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('public_id')->unique();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('instance_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->string('provider_event_type')->nullable();
            $table->string('provider_event_id')->nullable();
            $table->json('payload');
            $table->string('payload_hash');
            $table->timestamp('occurred_at');
            $table->timestamp('received_at');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['workspace_id', 'type']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
