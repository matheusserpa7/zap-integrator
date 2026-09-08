<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_objects', function (Blueprint $table) {
            $table->id();
            $table->string('public_id')->unique();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('instance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('mime_type');
            $table->string('filename');
            $table->unsignedBigInteger('size_bytes');
            $table->string('checksum');
            $table->string('disk_path');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['workspace_id', 'expires_at']);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('media_id')->nullable()->after('body')->constrained('media_objects')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('media_id');
        });

        Schema::dropIfExists('media_objects');
    }
};
