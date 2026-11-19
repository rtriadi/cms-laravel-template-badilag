<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->cascadeOnDelete();
            $table->string('location', 30)->default('header')->index();
            $table->string('title');
            $table->string('type', 30)->default('page');
            $table->unsignedBigInteger('page_id')->nullable()->index();
            $table->string('collection_slug')->nullable();
            $table->string('url')->nullable();
            $table->string('icon', 60)->nullable();
            $table->boolean('open_in_new_tab')->default(false);
            $table->boolean('is_visible')->default(true)->index();
            $table->integer('sort_order')->default(0);
            $table->string('criteria_code', 30)->nullable()->index();
            $table->string('tingkat_scope', 20)->default('all')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
