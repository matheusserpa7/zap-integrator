<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('public_id')->nullable()->after('id');
            $table->boolean('is_platform_admin')->default(false)->after('password');
            $table->timestamp('disabled_at')->nullable()->after('is_platform_admin');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('email_verified_at');
        });

        Schema::dropIfExists('password_reset_tokens');

        foreach (DB::table('users')->whereNull('public_id')->orderBy('id')->get() as $user) {
            DB::table('users')->where('id', $user->id)->update([
                'public_id' => 'usr_'.str_replace('-', '', Str::uuid7()->toString()),
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique('public_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['public_id']);
            $table->dropColumn(['public_id', 'is_platform_admin', 'disabled_at']);
            $table->timestamp('email_verified_at')->nullable()->after('email');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }
};
