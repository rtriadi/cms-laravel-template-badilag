<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('post_comments')->cascadeOnDelete();
            $table->string('author_name', 120);
            $table->string('author_email', 160)->nullable();
            $table->text('body');
            $table->string('status', 20)->default('pending')->index();
            $table->boolean('is_official_reply')->default(false);
            $table->string('ip_hash', 64)->nullable()->index();
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
            $table->timestamps();

            $table->index(['post_id', 'status', 'created_at'], 'post_comments_post_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_comments');
    }
};
