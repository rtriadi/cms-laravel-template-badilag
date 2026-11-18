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
        Schema::create('satker_settings', function (Blueprint $table) {
            $table->id();
            $table->string('nama_satker');
            $table->string('jenis_badan', 20)->default('PA');
            $table->string('tingkat', 20)->default('pertama');
            $table->boolean('is_tingkat_locked')->default(false);
            $table->string('kode_satker', 50)->nullable();
            $table->unsignedSmallInteger('tahun_berdiri')->nullable();
            $table->string('alamat_jalan')->nullable();
            $table->string('desa_kelurahan')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('kabupaten_kota')->nullable();
            $table->string('provinsi')->nullable();
            $table->string('kode_pos', 10)->nullable();
            $table->string('telepon', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('website_url')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('favicon_path')->nullable();
            $table->string('foto_gedung_path')->nullable();
            $table->string('sipp_url')->nullable();
            $table->string('ecourt_url')->nullable();
            $table->string('direktori_putusan_url')->nullable();
            $table->string('siwas_url')->nullable();
            $table->string('simtepa_pegawai_url')->nullable();
            $table->string('simtepa_struktur_url')->nullable();
            $table->string('skm_url')->nullable();
            $table->string('spak_url')->nullable();
            $table->boolean('is_setup_completed')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('satker_settings');
    }
};
