<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table) {
            $table->id();
            $table->string('public_id')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('owner_user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('max_instances')->nullable();
            $table->timestamps();

            $table->unique('owner_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspaces');
    }
};
