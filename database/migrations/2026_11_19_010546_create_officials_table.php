<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('officials', function (Blueprint $table) {
            $table->id();
            $table->string('nama_gelar');
            $table->string('jabatan');
            $table->string('kategori', 30)->default('pejabat')->index();
            $table->foreignId('parent_id')->nullable()->constrained('officials')->nullOnDelete();
            $table->string('pangkat_golongan', 80)->nullable();
            $table->string('tmt_jabatan', 60)->nullable();
            $table->text('riwayat_pendidikan')->nullable();
            $table->text('riwayat_jabatan')->nullable();
            $table->string('photo_path')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('officials');
    }
};
