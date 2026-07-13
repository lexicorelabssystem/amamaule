<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->string('file_path')->nullable()->change();
            $table->string('status')->default('completed');
            $table->string('pending_path')->nullable();
            $table->string('error_message')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropColumn(['status', 'pending_path', 'error_message']);
            $table->string('file_path')->nullable(false)->change();
        });
    }
};
