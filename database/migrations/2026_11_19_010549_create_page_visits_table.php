<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_visits', function (Blueprint $table) {
            $table->id();
            $table->date('visit_date');
            $table->string('path');
            $table->unsignedInteger('visits_count')->default(1);
            $table->timestamps();

            $table->unique(['visit_date', 'path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_visits');
    }
};
