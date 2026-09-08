<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_event_fingerprints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('instance_id')->constrained()->cascadeOnDelete();
            $table->string('fingerprint', 64);
            $table->string('provider_event_type');
            $table->string('provider_event_id')->nullable();
            $table->string('event_type');
            $table->jsonb('payload')->nullable();
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->unique(['instance_id', 'fingerprint']);
            $table->index(['instance_id', 'received_at']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_event_fingerprints');
    }
};
