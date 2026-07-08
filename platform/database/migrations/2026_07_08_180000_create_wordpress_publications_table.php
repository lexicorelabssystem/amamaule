<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wordpress_publications', function (Blueprint $table) {
            $table->id();
            $table->morphs('publishable');
            $table->unsignedBigInteger('wordpress_post_id')->nullable()->index();
            $table->string('wordpress_post_type')->default('posts');
            $table->string('status')->default('pending')->index();
            $table->string('content_hash', 64)->nullable();
            $table->text('wordpress_url')->nullable();
            $table->text('last_error')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->unique(['publishable_type', 'publishable_id'], 'wp_publications_publishable_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wordpress_publications');
    }
};
