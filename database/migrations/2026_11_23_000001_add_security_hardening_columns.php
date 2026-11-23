<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('satker_settings') && ! Schema::hasColumn('satker_settings', 'singleton_guard')) {
            Schema::table('satker_settings', function (Blueprint $table): void {
                $table->unsignedTinyInteger('singleton_guard')->default(1)->unique()->after('id');
            });
        }

        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'session_version')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->unsignedInteger('session_version')->default(1)->after('remember_token');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('satker_settings') && Schema::hasColumn('satker_settings', 'singleton_guard')) {
            Schema::table('satker_settings', function (Blueprint $table): void {
                $table->dropUnique(['singleton_guard']);
                $table->dropColumn('singleton_guard');
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'session_version')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('session_version');
            });
        }
    }
};
