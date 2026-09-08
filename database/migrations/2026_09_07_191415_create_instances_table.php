<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instances', function (Blueprint $table) {
            $table->id();
            $table->string('public_id')->unique();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('provider');
            $table->string('provider_instance_name')->unique();
            $table->text('provider_instance_token_encrypted')->nullable();
            $table->text('provider_webhook_secret_encrypted')->nullable();
            $table->string('status');
            $table->string('phone_number')->nullable();
            $table->text('qr_code_encrypted')->nullable();
            $table->timestamp('qr_expires_at')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->string('last_error')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['workspace_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instances');
    }
};
