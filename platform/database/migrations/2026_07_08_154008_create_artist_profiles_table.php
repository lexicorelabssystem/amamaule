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
        Schema::create('artist_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artist_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('experience')->nullable();
            $table->text('education')->nullable();
            $table->text('awards')->nullable();
            $table->string('portfolio_url')->nullable();
            $table->string('video_url')->nullable();
            $table->string('availability')->nullable();
            $table->text('representation')->nullable();
            $table->json('press_links')->nullable();
            $table->text('tech_rider')->nullable();
            $table->text('stage_requirements')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('artist_profiles');
    }
};
