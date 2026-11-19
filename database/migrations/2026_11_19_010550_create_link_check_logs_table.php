<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('link_check_logs', function (Blueprint $table) {
            $table->id();
            $table->string('source_type', 40)->index();
            $table->string('source_label');
            $table->string('url');
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->boolean('is_broken')->default(false)->index();
            $table->string('error_message')->nullable();
            $table->timestamp('checked_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('link_check_logs');
    }
};
