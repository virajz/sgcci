<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Note: Not using CONCURRENTLY since migrations run in transactions
        // These support the part payment features and export functionality

        // Index for part payment queries (WHERE amount_paid > 0)
        DB::statement('CREATE INDEX IF NOT EXISTS bookings_amount_paid_index ON bookings (amount_paid)');

        // Index for payment completion checks (WHERE remaining_amount > 0)
        DB::statement('CREATE INDEX IF NOT EXISTS bookings_remaining_amount_index ON bookings (remaining_amount)');

        // Index for payment deadline queries (used in automated reminders)
        DB::statement('CREATE INDEX IF NOT EXISTS bookings_partial_payment_deadline_index ON bookings (partial_payment_deadline)');

        // Composite index for export queries (date range + status filtering)
        // This optimizes: WHERE created_at BETWEEN ? AND ? AND status = ?
        DB::statement('CREATE INDEX IF NOT EXISTS bookings_created_status_manual_index ON bookings (created_at, status, is_manual_block)');

        // Composite index for payment deadline queries with remaining amount
        // This optimizes the paymentDeadlineApproaching and paymentOverdue scopes
        DB::statement('CREATE INDEX IF NOT EXISTS bookings_deadline_remaining_index ON bookings (partial_payment_deadline, remaining_amount)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes concurrently to avoid locking
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS bookings_amount_paid_index');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS bookings_remaining_amount_index');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS bookings_partial_payment_deadline_index');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS bookings_created_status_manual_index');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS bookings_deadline_remaining_index');
    }
};
