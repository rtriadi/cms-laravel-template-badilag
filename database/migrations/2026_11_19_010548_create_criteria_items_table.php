<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('criteria_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('criteria_group_id')->constrained('criteria_groups')->cascadeOnDelete();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('tingkat_scope', 20)->default('all')->index();
            $table->string('item_type', 20)->default('link');
            $table->foreignId('linked_page_id')->nullable()->constrained('pages')->nullOnDelete();
            $table->foreignId('linked_menu_id')->nullable()->constrained('menu_items')->nullOnDelete();
            $table->string('default_route')->nullable();
            $table->string('manual_url')->nullable();
            $table->boolean('use_manual_url')->default(false);
            $table->string('health_status', 20)->default('empty')->index();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('health_message')->nullable();
            $table->unsignedTinyInteger('estimated_score')->default(0);
            $table->timestamp('last_checked_at')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('criteria_items');
    }
};
