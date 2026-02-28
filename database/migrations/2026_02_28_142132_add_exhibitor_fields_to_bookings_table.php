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
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('exhibitor_user_id')->nullable()->constrained('users')->nullOnDelete()->after('id');
            $table->string('login_password')->nullable()->after('exhibitor_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['exhibitor_user_id']);
            $table->dropColumn(['exhibitor_user_id', 'login_password']);
        });
    }
};
