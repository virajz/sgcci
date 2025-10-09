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
            $table->boolean('is_manual_block')->default(false)->after('status');
            $table->foreignId('blocked_by')->nullable()->after('is_manual_block')->constrained('users')->nullOnDelete();
            $table->timestamp('blocked_at')->nullable()->after('blocked_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['blocked_by']);
            $table->dropColumn(['is_manual_block', 'blocked_by', 'blocked_at']);
        });
    }
};
