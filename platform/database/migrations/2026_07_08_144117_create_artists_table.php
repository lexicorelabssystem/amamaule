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
        Schema::create('artists', function (Blueprint $table) {
            $table->id();

            // User account (nullable for pre-registered artists without login yet)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Names
            $table->string('legal_name')->nullable();
            $table->string('public_name')->nullable();
            $table->string('artistic_name')->nullable();
            $table->string('slug')->unique()->nullable();

            // Contact
            $table->string('document_number')->nullable();
            $table->string('email_contact')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();

            // Location (redundant text fields as fallback + normalized territory)
            $table->string('region')->nullable();
            $table->string('province')->nullable();
            $table->string('commune')->nullable();
            $table->string('address')->nullable();
            $table->foreignId('territory_id')->nullable()->constrained('territories')->nullOnDelete();

            // Discipline
            $table->foreignId('main_discipline_id')->nullable()->constrained('disciplines')->nullOnDelete();

            // Biography
            $table->text('bio_short')->nullable();
            $table->longText('bio_long')->nullable();
            $table->json('social_networks')->nullable();

            // Status workflow
            $table->string('status', 30)->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();

            // Metadata
            $table->unsignedBigInteger('profile_views')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('user_id');
            $table->index('status');
            $table->index('territory_id');
            $table->index('main_discipline_id');
            $table->index('email_contact');
            $table->index('approved_by');
            $table->index(['status', 'territory_id']);
            $table->index(['status', 'main_discipline_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('artists');
    }
};
