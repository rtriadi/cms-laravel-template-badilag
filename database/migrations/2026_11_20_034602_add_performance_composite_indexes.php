<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->index(['is_published', 'category', 'is_pinned', 'published_at'], 'posts_pub_cat_pin_date_idx');
            $table->index(['is_published', 'expires_at'], 'posts_pub_exp_idx');
        });

        Schema::table('documents', function (Blueprint $table): void {
            $table->index(['collection_type', 'is_published', 'year', 'month'], 'documents_col_pub_ym_idx');
        });

        Schema::table('pages', function (Blueprint $table): void {
            $table->index(['is_published', 'tingkat_scope', 'slug'], 'pages_pub_scope_slug_idx');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->dropIndex('posts_pub_cat_pin_date_idx');
            $table->dropIndex('posts_pub_exp_idx');
        });

        Schema::table('documents', function (Blueprint $table): void {
            $table->dropIndex('documents_col_pub_ym_idx');
        });

        Schema::table('pages', function (Blueprint $table): void {
            $table->dropIndex('pages_pub_scope_slug_idx');
        });
    }
};
