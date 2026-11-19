<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('court_schedules', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_sidang')->index();
            $table->string('nomor_perkara', 100);
            $table->string('para_pihak');
            $table->string('ruang_sidang', 80)->nullable();
            $table->string('agenda')->nullable();
            $table->string('file_pdf_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('court_schedules');
    }
};
