<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_statistics', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->unsignedInteger('sisa_bulan_lalu')->default(0);
            $table->unsignedInteger('perkara_masuk')->default(0);
            $table->unsignedInteger('perkara_putus')->default(0);
            $table->unsignedInteger('sisa_saat_ini')->default(0);
            $table->timestamps();

            $table->unique(['year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_statistics');
    }
};
