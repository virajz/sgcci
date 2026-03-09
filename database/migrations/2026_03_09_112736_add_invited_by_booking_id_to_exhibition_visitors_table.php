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
        Schema::table('exhibition_visitors', function (Blueprint $table) {
            $table->foreignId('invited_by_booking_id')
                ->nullable()
                ->after('exhibition_id')
                ->constrained('bookings')
                ->nullOnDelete();
            $table->index('invited_by_booking_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exhibition_visitors', function (Blueprint $table) {
            $table->dropForeign(['invited_by_booking_id']);
            $table->dropIndex(['invited_by_booking_id']);
            $table->dropColumn('invited_by_booking_id');
        });
    }
};
