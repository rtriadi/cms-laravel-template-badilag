<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        Schema::table('posts', function (Blueprint $table): void {
            $table->fullText(['title', 'excerpt', 'body'], 'posts_search_fulltext');
        });

        Schema::table('documents', function (Blueprint $table): void {
            $table->fullText(['title', 'status_label'], 'documents_search_fulltext');
        });

        Schema::table('pages', function (Blueprint $table): void {
            $table->fullText(['title', 'summary'], 'pages_search_fulltext');
        });
    }

    public function down(): void
    {
        if (! in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        Schema::table('posts', function (Blueprint $table): void {
            $table->dropFullText('posts_search_fulltext');
        });

        Schema::table('documents', function (Blueprint $table): void {
            $table->dropFullText('documents_search_fulltext');
        });

        Schema::table('pages', function (Blueprint $table): void {
            $table->dropFullText('pages_search_fulltext');
        });
    }
};
