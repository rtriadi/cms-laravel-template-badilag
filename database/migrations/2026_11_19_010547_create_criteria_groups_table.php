<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('criteria_groups', function (Blueprint $table) {
            $table->id();
            $table->string('juknis_version', 20)->default('2026');
            $table->string('aspect', 20)->default('konten')->index();
            $table->string('code', 30);
            $table->string('name');
            $table->decimal('weight', 5, 2)->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['juknis_version', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('criteria_groups');
    }
};
