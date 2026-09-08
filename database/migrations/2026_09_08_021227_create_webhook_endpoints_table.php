<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_endpoints', function (Blueprint $table) {
            $table->id();
            $table->string('public_id')->unique();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('url');
            $table->string('description')->nullable();
            $table->string('event_type');
            $table->string('payload_mode');
            $table->json('body_mapping')->nullable();
            $table->text('headers_mapping_encrypted')->nullable();
            $table->text('secret_encrypted');
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('failure_count')->default(0);
            $table->timestamps();

            $table->index(['workspace_id', 'event_type', 'enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_endpoints');
    }
};
