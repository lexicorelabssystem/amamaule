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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('must_change_password')->default(true)->after('password');
            $table->string('status', 20)->default('active')->after('must_change_password');
            $table->timestamp('last_login_at')->nullable()->after('status');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->unsignedTinyInteger('failed_login_attempts')->default(0)->after('last_login_ip');
            $table->timestamp('locked_until')->nullable()->after('failed_login_attempts');
            $table->unsignedBigInteger('wordpress_user_id')->nullable()->unique()->after('locked_until');

            $table->index('status');
            $table->index('wordpress_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['wordpress_user_id']);
            $table->dropColumn([
                'must_change_password',
                'status',
                'last_login_at',
                'last_login_ip',
                'failed_login_attempts',
                'locked_until',
                'wordpress_user_id',
            ]);
        });
    }
};
