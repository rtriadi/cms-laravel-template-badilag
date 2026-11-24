<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('satker_settings', function (Blueprint $table): void {
            $table->string('pta_nama')->nullable()->after('website_url');
            $table->string('pta_url', 500)->nullable()->after('pta_nama');
            $table->string('survei_periode', 100)->nullable()->after('spak_url');
            $table->string('nilai_skm', 32)->nullable()->after('survei_periode');
            $table->string('nilai_spak', 32)->nullable()->after('nilai_skm');
            $table->string('nilai_spkp', 32)->nullable()->after('nilai_spak');
            $table->string('video_profil_url', 500)->nullable()->after('nilai_spkp');
            $table->string('video_ptsp_url', 500)->nullable()->after('video_profil_url');
            $table->string('video_kebijakan_ma_url', 500)->nullable()->after('video_ptsp_url');
        });
    }

    public function down(): void
    {
        Schema::table('satker_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'pta_nama',
                'pta_url',
                'survei_periode',
                'nilai_skm',
                'nilai_spak',
                'nilai_spkp',
                'video_profil_url',
                'video_ptsp_url',
                'video_kebijakan_ma_url',
            ]);
        });
    }
};
