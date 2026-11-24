<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('satker_settings', function (Blueprint $table): void {
            $table->string('cctv_aco_url', 500)->nullable()->after('video_kebijakan_ma_url');
            $table->string('cctv_aco_label', 120)->nullable()->after('cctv_aco_url');
            $table->string('social_facebook_url', 500)->nullable()->after('cctv_aco_label');
            $table->string('social_instagram_url', 500)->nullable()->after('social_facebook_url');
            $table->string('social_tiktok_url', 500)->nullable()->after('social_instagram_url');
            $table->string('social_youtube_url', 500)->nullable()->after('social_tiktok_url');
            $table->string('social_x_url', 500)->nullable()->after('social_youtube_url');
            $table->json('related_links')->nullable()->after('social_x_url');
        });
    }

    public function down(): void
    {
        Schema::table('satker_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'cctv_aco_url',
                'cctv_aco_label',
                'social_facebook_url',
                'social_instagram_url',
                'social_tiktok_url',
                'social_youtube_url',
                'social_x_url',
                'related_links',
            ]);
        });
    }
};
